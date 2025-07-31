# Dashboard Widget Test Guide

## Testing the LIFT Docs Dashboard Widget

### 1. Widget Appearance
The dashboard widget should appear on the WordPress admin dashboard with:
- **Title**: "🗂️ LIFT Documents Overview"  
- **Position**: High priority in normal column
- **Visibility**: Only for users with `manage_options` capability or `documents_user` role

### 2. Widget Features

#### Statistics Section (4 boxes):
- **Total Docs**: Count of all documents
- **Pending**: Documents with 'pending' status  
- **Processing**: Documents with 'processing' status
- **Completed**: Documents with 'done' status

#### Documents Table with columns:
1. **Document Name**: Title + creation date
2. **Status**: Color-coded badge (pending/processing/done/cancelled)
3. **Assigned Users**: Display names (max 2 shown, +X for more)
4. **Action**: "View" button linking to filtered documents list

### 3. User Role Behavior

#### For Administrators:
- See all documents (up to 10 most recent)
- Statistics include all documents in system
- "View" button links to documents list filtered by assigned user

#### For documents_user role:
- See only documents assigned to them
- Statistics show only their assigned documents  
- "View" button links to documents list filtered by their user ID

### 4. Testing Steps

#### Step 1: Create Test Data
```sql
-- Create test documents with different statuses
INSERT INTO wp_posts (post_title, post_type, post_status) VALUES 
('Test Document 1', 'lift_document', 'publish'),
('Test Document 2', 'lift_document', 'publish'),
('Test Document 3', 'lift_document', 'publish');

-- Add document statuses
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(1, '_lift_doc_status', 'pending'),
(2, '_lift_doc_status', 'processing'), 
(3, '_lift_doc_status', 'done');

-- Assign users to documents
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES
(1, '_lift_doc_users', '["1","2"]'),
(2, '_lift_doc_users', '["2"]'),
(3, '_lift_doc_users', '["1"]');
```

#### Step 2: Access Dashboard
1. Go to WordPress Admin Dashboard (`/wp-admin/`)
2. Look for "LIFT Documents Overview" widget
3. Verify statistics are correct
4. Check table shows correct documents

#### Step 3: Test User Filtering
1. Click "View" button on any document row
2. Should redirect to Documents list page
3. URL should contain filter parameter:
   - Admin: `?lift_docs_user_filter=USER_ID`
   - Regular user: `?author=USER_ID`

#### Step 4: Test Responsive Design
1. Resize browser window
2. Widget should adapt to smaller screens
3. Table should remain readable

### 5. Expected CSS Classes

The widget uses these CSS classes for styling:
- `.lift-docs-widget` - Main container
- `.lift-docs-widget-stats` - Statistics section
- `.lift-docs-stat-box` - Individual stat boxes
- `.lift-docs-documents-table` - Main table
- `.lift-docs-status` - Status badges with colors
- `.lift-docs-action-btn` - View buttons

### 6. Troubleshooting

#### Widget not showing:
- Check user has correct capabilities
- Verify plugin is active
- Check for PHP errors in logs

#### Statistics incorrect:
- Verify documents have correct post_type
- Check meta fields are saved properly
- Ensure user assignments are in correct format

#### Filter not working:
- Check URL parameters in browser
- Verify filter functions in admin class
- Test with different user roles

### 7. Browser Console Debug

```javascript
// Check if widget is loaded
console.log('Widget elements:', document.querySelectorAll('.lift-docs-widget'));

// Check statistics
document.querySelectorAll('.lift-docs-stat-number').forEach(stat => {
    console.log('Stat value:', stat.textContent);
});

// Check table data
console.log('Documents in table:', document.querySelectorAll('.lift-docs-documents-table tbody tr').length);
```

### 8. Performance Notes

- Widget limits to 10 most recent documents
- Statistics query includes all documents but only fetches IDs
- User data is cached per document row
- CSS is inlined to avoid extra HTTP requests

## Status: ✅ READY FOR TESTING

Dashboard widget is fully implemented with responsive design and user role filtering!
