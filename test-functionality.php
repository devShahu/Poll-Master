<?php
/**
 * Test file to check all PollMaster functionality
 * Run this file to validate database structure and functionality
 */

// Include WordPress
require_once('../../../wp-config.php');

// Include PollMaster classes
require_once('includes/class-pollmaster-database.php');

echo "<h1>PollMaster Functionality Test</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    global $wpdb;
    $result = $wpdb->get_var("SELECT 1");
    echo $result === '1' ? "✅ Database connection successful<br>" : "❌ Database connection failed<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: Table Structure
echo "<h2>2. Table Structure Test</h2>";
$database = new PollMaster_Database();
$tables_exist = $database->check_tables_exist();
echo $tables_exist ? "✅ All tables exist<br>" : "❌ Some tables are missing<br>";

// Test 3: Poll Creation
echo "<h2>3. Poll Creation Test</h2>";
try {
    $poll_data = array(
        'question' => 'Test Poll Question',
        'option_a' => 'Option A',
        'option_b' => 'Option B',
        'description' => 'Test description',
        'is_contest' => 0,
        'is_weekly' => 0
    );
    
    $poll_id = $database->create_poll($poll_data);
    echo $poll_id ? "✅ Poll created successfully (ID: $poll_id)<br>" : "❌ Poll creation failed<br>";
    
    // Test 4: Poll Retrieval
    echo "<h2>4. Poll Retrieval Test</h2>";
    $poll = $database->get_poll_admin($poll_id);
    echo $poll ? "✅ Poll retrieved successfully<br>" : "❌ Poll retrieval failed<br>";
    
    // Test 5: Poll Results
    echo "<h2>5. Poll Results Test</h2>";
    $results = $database->get_poll_results($poll_id);
    echo isset($results['total_votes']) ? "✅ Poll results retrieved successfully<br>" : "❌ Poll results retrieval failed<br>";
    
    // Test 6: Pagination
    echo "<h2>6. Pagination Test</h2>";
    $paginated_polls = $database->get_polls_with_pagination(1, 10);
    echo isset($paginated_polls['polls']) ? "✅ Pagination working<br>" : "❌ Pagination failed<br>";
    
    // Clean up test poll
    $database->delete_poll($poll_id);
    echo "✅ Test poll cleaned up<br>";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "<br>";
}

// Test 7: JavaScript Functions
echo "<h2>7. JavaScript Functions Test</h2>";
echo "<button onclick='testExportFunction()'>Test Export Function</button><br>";
echo "<div id='export-test-result'></div>";

echo "<script>
function testExportFunction() {
    if (typeof exportPollData === 'function') {
        document.getElementById('export-test-result').innerHTML = '✅ exportPollData function exists';
    } else {
        document.getElementById('export-test-result').innerHTML = '❌ exportPollData function not found';
    }
}
</script>";

echo "<h2>Test Complete</h2>";
echo "<p>If all tests show ✅, your PollMaster plugin is working correctly!</p>";
?>