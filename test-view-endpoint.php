<?php
/**
 * Test file for the new view endpoint functionality
 * This is a temporary test file to demonstrate the new view endpoint
 */

// Example usage of the new view endpoint:

// For a document with ID 123, you can now generate both download and view URLs:

/*
// Download URL (existing functionality):
$download_url = LIFT_Docs_Settings::generate_secure_download_link(123);
// Result: http://yoursite.com/document-files/download/?lift_secure=token_here

// View URL (new functionality):
$view_url = LIFT_Docs_Settings::generate_secure_view_link(123);
// Result: http://yoursite.com/document-files/view/?lift_secure=token_here

// For multiple files, specify file index:
$download_url_file_2 = LIFT_Docs_Settings::generate_secure_download_link(123, 0, 1);
$view_url_file_2 = LIFT_Docs_Settings::generate_secure_view_link(123, 0, 1);
*/

// The key differences:
// 1. Download endpoint: Forces file download with "Content-Disposition: attachment"
// 2. View endpoint: Shows file inline in browser with "Content-Disposition: inline"

// Both endpoints:
// - Use the same security token verification
// - Have the same permission checks
// - Support multiple files with file index
// - Track access (using the same download tracking for now)

echo "View endpoint implementation completed!\n";
echo "New endpoints available:\n";
echo "- Download: /document-files/download/?lift_secure=TOKEN\n";
echo "- View: /document-files/view/?lift_secure=TOKEN\n";
echo "\nTo use the new functionality:\n";
echo "1. Generate view URL with: LIFT_Docs_Settings::generate_secure_view_link(\$document_id)\n";
echo "2. The view URL will display the file inline in the browser instead of downloading it\n";
echo "3. Both endpoints use the same security and permission system\n";
?>
