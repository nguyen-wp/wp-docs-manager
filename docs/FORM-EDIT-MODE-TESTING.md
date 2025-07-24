# Form Edit Mode Testing Guide

## Feature Overview
After this implementation, the form submission behavior will be:

1. **First Time Submission:**
   - User sees normal form
   - Can submit form once
   - After submission, redirected to document dashboard

2. **Subsequent Access:**
   - User sees "Edit Mode" notice
   - Form is pre-populated with previous submission data
   - Submit button says "Update Submission"
   - After update, redirected to document dashboard

3. **Document Dashboard:**
   - Form buttons show as "Edit [Form Name]" if user has submitted
   - Button changes color to indicate edit mode
   - Shows edit icon

## Database Changes Made

1. **Added functions to check if user submitted form:**
   ```php
   user_has_submitted_form($user_id, $form_id, $document_id)
   get_user_submission($user_id, $form_id, $document_id)
   ```

2. **Enhanced AJAX submission handler:**
   - Supports both INSERT (new) and UPDATE (edit) operations
   - Validates user can only edit their own submissions
   - Different success messages for new/edit

3. **Form rendering enhancements:**
   - Pre-populates form fields with existing data
   - Shows edit mode notice
   - Updates button text and functionality

## Testing Steps

### Test 1: First Time Form Submission
1. Login as a user
2. Go to document dashboard
3. Click on a form button (should show form name)
4. Fill form and submit
5. Should redirect to document dashboard
6. Form button should now show "Edit [Form Name]" with edit icon

### Test 2: Form Edit Mode
1. Click the "Edit [Form Name]" button
2. Should see:
   - "Edit Mode" notice with original submission date
   - Form pre-filled with previous data
   - "Update Submission" button instead of "Submit Form"
3. Modify some fields and click update
4. Should redirect back to dashboard with success message

### Test 3: Security Checks
1. Try to edit another user's submission (should fail)
2. Try to access form without proper permissions
3. Verify nonce and capability checks work

## Database Query Examples

```sql
-- Check if user has submitted form for document
SELECT id FROM wp_lift_form_submissions 
WHERE form_id = X AND user_id = Y AND JSON_EXTRACT(form_data, '$._document_id') = Z;

-- Get user's submission for editing
SELECT * FROM wp_lift_form_submissions 
WHERE form_id = X AND user_id = Y AND JSON_EXTRACT(form_data, '$._document_id') = Z 
ORDER BY submitted_at DESC LIMIT 1;
```

## Files Modified

1. **class-lift-forms.php**
   - Added user submission check functions
   - Enhanced AJAX submit handler for edit mode
   - Added edit mode support in form processing

2. **class-lift-docs-frontend-login.php**
   - Updated form rendering to support edit mode
   - Added pre-population of form fields
   - Enhanced document dashboard buttons
   - Modified redirect behavior

3. **Frontend user experience**
   - Clear indication of edit vs new submission
   - Proper button labeling and styling
   - Automatic redirect to dashboard after submission

## Potential Issues to Test

1. **JSON field extraction compatibility** - Test on different MySQL versions
2. **User session handling** - Test with different user roles
3. **Form field types** - Test all field types (text, textarea, select, etc.)
4. **Multiple forms per document** - Ensure each form tracks separately
5. **Guest users** - Ensure they can still submit (but not edit)

## Success Criteria

✅ User can submit form only once per document
✅ After submission, user is redirected to dashboard  
✅ Subsequent access shows edit mode with pre-filled data
✅ Form buttons reflect submission status
✅ Updates work correctly and maintain data integrity
✅ Security checks prevent unauthorized edits
✅ Guest users can still submit new forms
