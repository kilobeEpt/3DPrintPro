<?php

namespace App\Http\Traits;

/**
 * ManagesSlugs Trait
 * 
 * Provides slug generation and deduplication for controllers.
 */
trait ManagesSlugs
{
    /**
     * Generate a unique slug from text
     * 
     * @param string $text Source text for slug
     * @param string $modelClass Model class name
     * @param int|null $excludeId ID to exclude from uniqueness check (for updates)
     * @return string Unique slug
     */
    protected function generateUniqueSlug($text, $modelClass, $excludeId = null)
    {
        $slug = $this->generateSlug($text);
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $modelClass, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Generate a URL-friendly slug from text
     * 
     * @param string $text Source text
     * @return string Slug
     */
    protected function generateSlug($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        
        // Transliterate Cyrillic to Latin
        $text = $this->transliterate($text);
        
        // Remove special characters except spaces and hyphens
        $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
        
        // Replace multiple spaces/hyphens with single hyphen
        $text = preg_replace('/[\s-]+/', '-', $text);
        
        // Trim hyphens from start and end
        return trim($text, '-');
    }
    
    /**
     * Check if slug already exists
     * 
     * @param string $slug Slug to check
     * @param string $modelClass Model class name
     * @param int|null $excludeId ID to exclude from check
     * @return bool True if exists
     */
    protected function slugExists($slug, $modelClass, $excludeId = null)
    {
        $query = $modelClass::where('slug', $slug);
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    /**
     * Transliterate Cyrillic characters to Latin
     * 
     * @param string $text Text to transliterate
     * @return string Transliterated text
     */
    protected function transliterate($text)
    {
        $transliteration = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];
        
        return strtr($text, $transliteration);
    }
}
