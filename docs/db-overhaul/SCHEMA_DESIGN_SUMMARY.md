# Schema Design Summary
**3D Print Pro Database v3.0 Target Schema**  
**Status:** ✅ Design Complete - Ready for Review

---

## Quick Facts

| Metric | Current (v2.0) | Target (v3.0) | Change |
|--------|----------------|---------------|--------|
| **Tables** | 7 | 19 | +171% |
| **Foreign Keys** | 0 | 34 | ∞ |
| **Indexes** | 38 | 65 | +71% |
| **Normalization** | Partial | 3NF | ✅ |
| **Audit Trail** | Timestamps only | Complete | ✅ |
| **Soft Deletes** | No | Yes | ✅ |

---

## Document Links

📄 **[current-state.md](./current-state.md)** (1,696 lines, 56 KB)  
→ Complete audit of existing database  
→ 25 identified gaps across 5 categories  
→ Risk assessment and remediation roadmap

📄 **[schema-design.md](./schema-design.md)** (2,960 lines, 92 KB)  
→ Full 3NF normalized target schema  
→ 19 entity definitions with relationships  
→ Migration strategy and legacy mapping  
→ 10 open questions for stakeholders

📄 **[README.md](./README.md)**  
→ Directory overview and usage guide  
→ Next steps and acceptance criteria

---

## Key Design Decisions

### ✅ Core Improvements
1. **Referential Integrity** - 34 FK relationships with explicit CASCADE rules
2. **Separated Concerns** - Dedicated `users`, `customers`, `settings` tables
3. **Normalized Lookups** - `categories`, `order_statuses`, `materials` tables
4. **Audit Logging** - Per-table tracking + centralized `audit_log`
5. **Soft Deletes** - Recoverable deletions with `deleted_at` timestamps
6. **Performance** - 65 indexes including 15 composite indexes

### 🎯 Strategic Denormalization
1. **orders.customer_snapshot** - JSON snapshot of customer at order time
2. **orders.calculator_data** - JSON (complex nested structure, rarely queried)
3. **services.price_display** - VARCHAR for formatted display (alongside numeric fields)

### 📊 New Tables (12)
- `users` - Admin authentication (from settings)
- `customers` - Unified customer records (from orders)
- `categories` - Shared taxonomy (from VARCHAR fields)
- `materials` - 3D printing materials catalog
- `order_statuses` - Status lookup (from ENUM)
- `order_types` - Type lookup (from ENUM)
- `order_status_history` - Status change audit trail
- `service_features` - Normalized features (from JSON)
- `tags` - Portfolio tag taxonomy
- `portfolio_tags` - Portfolio↔Tags junction
- `content_revisions` - Version history
- `audit_log` - Centralized change log

---

## Migration Strategy

### 6 Phases

**Phase 1: Pre-Migration Validation** (1 day)
- Backup production database
- Validate data quality
- Test migration scripts on staging

**Phase 2: Schema Creation** (2 hours downtime)
- Create 19 new tables
- Create 65 indexes
- Seed lookup tables

**Phase 3: Data Migration** (4 hours)
- Extract customers from orders
- Normalize services features
- Normalize portfolio tags
- Link all foreign keys

**Phase 4: Application Code Updates** (2 weeks)
- Update Database class (transactions, soft deletes)
- Update 7 API endpoints
- Update admin panel
- Update helpers

**Phase 5: Testing & Validation** (1 week)
- Unit tests for DB methods
- Integration tests for APIs
- Validate FK constraints
- Test soft deletes and audit log

**Phase 6: Deployment** (4 hours maintenance)
- Final backup
- Execute migration
- Deploy code
- Monitor and optimize

---

## Open Questions Requiring Decisions

### Business Logic (5 questions)
1. **Customer Deduplication** - Merge by email or phone? Manual review?
2. **Order Service History** - Hard FK or soft FK with snapshot?
3. **Testimonial Moderation** - Who approves? Auto-approve verified customers?
4. **Content Revision Retention** - Keep all or last 10 per block?
5. **Audit Log Archival** - 90 days online then cold storage?

### Technical (5 questions)
6. **Material Price Updates** - Affect existing orders or snapshot at order time?
7. **Soft Delete Cascade** - One level or full cascade?
8. **Full-Text Search** - MySQL FULLTEXT or Elasticsearch?
9. **Cache Invalidation** - Coarse-grained or fine-grained?
10. **Transaction Isolation** - READ COMMITTED or REPEATABLE READ?

### Stakeholder Input (5 areas)
- **SEO Priority** - How important for services/portfolio?
- **Multi-Admin Support** - Need RBAC or simple admin/viewer?
- **Customer Portal** - Will customers login in future?
- **Internationalization** - Multi-language support needed?
- **Performance SLA** - Acceptable response times?

---

## Success Criteria

### ✅ Design Phase (COMPLETE)
- [x] Fully normalized schema (≥3NF)
- [x] All entities defined with PK/FK/indexes
- [x] Mermaid ERD with all relationships
- [x] Audit trail and soft delete strategy
- [x] Complete legacy field mapping
- [x] Migration plan with rollback procedures
- [x] Open questions documented

### ⏳ Implementation Phase (PENDING)
- [ ] Stakeholder approval of design
- [ ] 11 migration SQL scripts created
- [ ] Dry-run migration successful
- [ ] Application code updated
- [ ] All tests passing
- [ ] Production migration complete

---

## Timeline

```
Week 1: Stakeholder Review & Approval
Week 2: Migration Script Development
Weeks 3-6: Implementation & Testing
Week 7: Production Deployment
```

**Estimated Total:** 7 weeks from approval to production

---

## Risk Assessment

### 🔴 Critical Mitigation
- **Data Loss Risk** → Full backup before migration, tested rollback
- **Downtime Risk** → Staged migration (2hr + 4hr windows), tested on staging
- **Application Breakage** → Comprehensive testing, incremental deployment

### 🟡 Medium Mitigation
- **Performance Regression** → 65 indexes optimized for query patterns, load testing
- **Migration Errors** → 11 separate scripts, validated on production clone
- **Training Gap** → Documentation, code examples, transition support

---

## Contact & Approval

| Role | Name | Status |
|------|------|--------|
| **Database Architect** | | ⏳ Pending Review |
| **Lead Developer** | | ⏳ Pending Review |
| **Project Manager** | | ⏳ Pending Review |
| **Business Owner** | | ⏳ Pending Review |

**Next Action:** Schedule stakeholder review meeting  
**Review Materials:** schema-design.md (full specification)

---

**Created:** January 2025  
**Version:** 1.0  
**Status:** ✅ Ready for Stakeholder Review
