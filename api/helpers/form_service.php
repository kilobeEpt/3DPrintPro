<?php
// ========================================
// Form Service Helper
// Handles form loading, validation, and submission processing
// ========================================

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap/eloquent.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/telegram.php';

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use App\Models\Order;

class FormService {
    
    /**
     * Load form definition by slug with active fields
     * 
     * @param string $slug Form slug
     * @param bool $activeOnly Load only active forms and fields
     * @return array|null Form data with fields or null if not found
     */
    public static function loadForm($slug, $activeOnly = true) {
        try {
            $query = Form::with('fields')->bySlug($slug);
            
            if ($activeOnly) {
                $query->where('active', true);
            }
            
            $form = $query->first();
            
            if (!$form) {
                return null;
            }
            
            // Filter fields by active status if needed
            if ($activeOnly) {
                $form->setRelation('fields', $form->fields->where('active', true)->sortBy('sort_order')->values());
            }
            
            return [
                'id' => $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
                'description' => $form->description,
                'settings' => $form->settings ?? [],
                'notification_email' => $form->notification_email,
                'success_message' => $form->success_message,
                'redirect_url' => $form->redirect_url,
                'fields' => $form->fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'name' => $field->name,
                        'label' => $field->label,
                        'type' => $field->type,
                        'placeholder' => $field->placeholder,
                        'default_value' => $field->default_value,
                        'validation_rules' => $field->validation_rules ?? [],
                        'options' => $field->options ?? [],
                        'help_text' => $field->help_text,
                        'sort_order' => $field->sort_order,
                        'required' => $field->required,
                    ];
                })->all()
            ];
            
        } catch (Exception $e) {
            ApiLogger::error("Error loading form", [
                'slug' => $slug,
                'exception' => $e
            ]);
            return null;
        }
    }
    
    /**
     * Validate form submission data against form field rules
     * 
     * @param array $formData Form definition from loadForm()
     * @param array $submittedData User-submitted data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateSubmission($formData, $submittedData) {
        $errors = [];
        
        foreach ($formData['fields'] as $field) {
            $fieldName = $field['name'];
            $value = $submittedData[$fieldName] ?? null;
            
            // Check required fields
            if ($field['required'] && (empty($value) && $value !== '0' && $value !== 0)) {
                $errors[$fieldName] = $field['label'] . ' is required';
                continue;
            }
            
            // Skip validation if field is empty and not required
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }
            
            // Apply validation rules
            $rules = $field['validation_rules'] ?? [];
            $fieldErrors = self::applyValidationRules($fieldName, $value, $rules, $field['label'], $field['type']);
            
            if (!empty($fieldErrors)) {
                $errors[$fieldName] = $fieldErrors;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Apply validation rules to a field value
     * 
     * @param string $fieldName Field name
     * @param mixed $value Field value
     * @param array $rules Validation rules
     * @param string $label Field label for error messages
     * @param string $type Field type
     * @return string|null Error message or null if valid
     */
    private static function applyValidationRules($fieldName, $value, $rules, $label, $type) {
        // Type-based validation
        switch ($type) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $label . ' must be a valid email address';
                }
                break;
                
            case 'phone':
                // Basic phone validation - remove spaces and check if it's numeric-ish
                $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $value);
                if (!preg_match('/^[\+]?[0-9]{10,15}$/', $cleanPhone)) {
                    return $label . ' must be a valid phone number';
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    return $label . ' must be a number';
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return $label . ' must be a valid URL';
                }
                break;
        }
        
        // Apply custom validation rules
        foreach ($rules as $rule => $ruleValue) {
            switch ($rule) {
                case 'min':
                    if (is_numeric($value) && $value < $ruleValue) {
                        return $label . ' must be at least ' . $ruleValue;
                    }
                    break;
                    
                case 'max':
                    if (is_numeric($value) && $value > $ruleValue) {
                        return $label . ' must be at most ' . $ruleValue;
                    }
                    break;
                    
                case 'minLength':
                    if (strlen($value) < $ruleValue) {
                        return $label . ' must be at least ' . $ruleValue . ' characters';
                    }
                    break;
                    
                case 'maxLength':
                    if (strlen($value) > $ruleValue) {
                        return $label . ' must be at most ' . $ruleValue . ' characters';
                    }
                    break;
                    
                case 'pattern':
                    if (!preg_match('/' . $ruleValue . '/', $value)) {
                        return $label . ' format is invalid';
                    }
                    break;
            }
        }
        
        return null;
    }
    
    /**
     * Process form submission: create submission record, values, and optionally order
     * 
     * @param array $formData Form definition
     * @param array $submittedData Validated submitted data
     * @param array $metadata Additional metadata (IP, user agent, etc.)
     * @param Database|null $db Legacy database instance for Telegram/settings
     * @return array ['success' => bool, 'submission_id' => int|null, 'order_id' => int|null, 'error' => string|null]
     */
    public static function processSubmission($formData, $submittedData, $metadata = [], $db = null) {
        try {
            // Create form submission record
            $submission = FormSubmission::create([
                'form_id' => $formData['id'],
                'form_slug' => $formData['slug'],
                'submitted_data' => $submittedData,
                'status' => FormSubmission::STATUS_PENDING,
                'ip_address' => $metadata['ip_address'] ?? null,
                'user_agent' => $metadata['user_agent'] ?? null,
                'submitted_at' => now(),
            ]);
            
            // Create individual field values for queryability
            foreach ($formData['fields'] as $field) {
                $fieldName = $field['name'];
                $fieldValue = $submittedData[$fieldName] ?? null;
                
                // Convert arrays to JSON strings for storage
                if (is_array($fieldValue)) {
                    $fieldValue = json_encode($fieldValue, JSON_UNESCAPED_UNICODE);
                }
                
                FormSubmissionValue::create([
                    'form_submission_id' => $submission->id,
                    'form_field_id' => $field['id'],
                    'field_name' => $fieldName,
                    'field_value' => $fieldValue,
                ]);
            }
            
            ApiLogger::info("Form submission created", [
                'form_slug' => $formData['slug'],
                'submission_id' => $submission->id,
            ]);
            
            // Create or update order if this is an order/contact form
            $orderId = null;
            if (in_array($formData['slug'], ['order', 'contact'])) {
                $orderId = self::createOrderFromSubmission($formData, $submittedData, $submission->id);
                
                if ($orderId) {
                    ApiLogger::info("Order created from submission", [
                        'submission_id' => $submission->id,
                        'order_id' => $orderId,
                    ]);
                }
            }
            
            // Send Telegram notification
            if ($db) {
                self::sendTelegramNotification($formData, $submittedData, $submission->id, $orderId, $db);
            }
            
            return [
                'success' => true,
                'submission_id' => $submission->id,
                'order_id' => $orderId,
                'error' => null,
            ];
            
        } catch (Exception $e) {
            ApiLogger::error("Error processing form submission", [
                'form_slug' => $formData['slug'],
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'submission_id' => null,
                'order_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Create order from form submission
     * 
     * @param array $formData Form definition
     * @param array $submittedData Submitted data
     * @param int $submissionId Form submission ID
     * @return int|null Order ID or null on failure
     */
    private static function createOrderFromSubmission($formData, $submittedData, $submissionId) {
        try {
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Determine order type
            $type = $formData['slug'] === 'order' ? Order::TYPE_ORDER : Order::TYPE_CONTACT;
            
            // Map form data to order fields
            $orderData = [
                'order_number' => $orderNumber,
                'type' => $type,
                'name' => $submittedData['name'] ?? '',
                'phone' => $submittedData['phone'] ?? '',
                'email' => $submittedData['email'] ?? null,
                'telegram' => $submittedData['telegram'] ?? null,
                'service' => $submittedData['service'] ?? ($type === Order::TYPE_CONTACT ? 'Обращение' : null),
                'subject' => $submittedData['subject'] ?? null,
                'message' => $submittedData['message'] ?? null,
                'amount' => 0,
                'calculator_data' => null,
                'status' => Order::STATUS_NEW,
                'telegram_sent' => false,
                'form_submission_id' => $submissionId,
                'form_slug' => $formData['slug'],
            ];
            
            // Handle calculator data if present
            if (isset($submittedData['calculatorData']) && is_array($submittedData['calculatorData'])) {
                $orderData['calculator_data'] = $submittedData['calculatorData'];
                $orderData['amount'] = $submittedData['calculatorData']['totalCost'] ?? 0;
            } elseif (isset($submittedData['amount'])) {
                $orderData['amount'] = $submittedData['amount'];
            }
            
            $order = Order::create($orderData);
            
            return $order->id;
            
        } catch (Exception $e) {
            ApiLogger::error("Error creating order from submission", [
                'submission_id' => $submissionId,
                'exception' => $e
            ]);
            return null;
        }
    }
    
    /**
     * Send Telegram notification for form submission
     * 
     * @param array $formData Form definition
     * @param array $submittedData Submitted data
     * @param int $submissionId Submission ID
     * @param int|null $orderId Order ID if created
     * @param Database $db Database instance
     * @return void
     */
    private static function sendTelegramNotification($formData, $submittedData, $submissionId, $orderId, $db) {
        try {
            // For order/contact forms, use existing TelegramHelper
            if (in_array($formData['slug'], ['order', 'contact']) && $orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $orderNumber = $order->order_number;
                    $telegramResult = TelegramHelper::sendOrderNotification(
                        array_merge($submittedData, ['order_number' => $orderNumber]),
                        $orderNumber,
                        $orderId,
                        $db
                    );
                    
                    // Update order telegram status
                    $order->update([
                        'telegram_sent' => $telegramResult['success'],
                        'telegram_error' => $telegramResult['error'] ?? null,
                    ]);
                    
                    if (!$telegramResult['success']) {
                        ApiLogger::warning("Telegram notification failed for order", [
                            'order_id' => $orderId,
                            'error' => $telegramResult['error']
                        ]);
                    }
                }
            } else {
                // For other forms, send generic notification
                self::sendGenericFormNotification($formData, $submittedData, $submissionId, $db);
            }
            
        } catch (Exception $e) {
            ApiLogger::error("Error sending Telegram notification", [
                'submission_id' => $submissionId,
                'exception' => $e
            ]);
        }
    }
    
    /**
     * Send generic form submission notification
     * 
     * @param array $formData Form definition
     * @param array $submittedData Submitted data
     * @param int $submissionId Submission ID
     * @param Database $db Database instance
     * @return void
     */
    private static function sendGenericFormNotification($formData, $submittedData, $submissionId, $db) {
        // Get credentials from TelegramHelper via reflection
        $credentials = self::getTelegramCredentials($db);
        
        if (empty($credentials['botToken']) || empty($credentials['chatId'])) {
            return;
        }
        
        // Build message
        $message = "📝 <b>Новая заявка: " . htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8') . "</b>\n\n";
        $message .= "🆔 <b>ID заявки:</b> {$submissionId}\n\n";
        
        foreach ($formData['fields'] as $field) {
            $fieldName = $field['name'];
            $value = $submittedData[$fieldName] ?? null;
            
            if ($value !== null && $value !== '') {
                $label = htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');
                $displayValue = is_array($value) ? implode(', ', $value) : $value;
                $displayValue = htmlspecialchars($displayValue, ENT_QUOTES, 'UTF-8');
                $message .= "<b>{$label}:</b> {$displayValue}\n";
            }
        }
        
        $message .= "\n⏰ <b>Дата:</b> " . date('d.m.Y H:i');
        
        // Send via Telegram API
        $url = "https://api.telegram.org/bot" . $credentials['botToken'] . "/sendMessage";
        $postData = [
            'chat_id' => $credentials['chatId'],
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * Get Telegram credentials (helper method to access private TelegramHelper method)
     */
    private static function getTelegramCredentials($db) {
        $botToken = '';
        $chatId = '';
        
        if ($db) {
            try {
                $dbBotToken = $db->getSetting('telegram_bot_token');
                if ($dbBotToken && !empty($dbBotToken)) {
                    $botToken = $dbBotToken;
                }
                
                $dbChatId = $db->getSetting('telegram_chat_id');
                if ($dbChatId && !empty($dbChatId)) {
                    $chatId = $dbChatId;
                }
            } catch (Exception $e) {
                // Ignore
            }
        }
        
        return ['botToken' => $botToken, 'chatId' => $chatId];
    }
}
