<?php
error_reporting(E_WARNING || E_ERROR);
function test_failure($errno, $errstr, $errfile, $errline)
{   //if($errno == E_USER_ERROR || $errno == E_USER_WARNING) {
    	echo "Test failure: [$errno] $errstr\non line $errline in file $errfile";
    	echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
    	echo "Aborting...<br />\n";
    	exit(1);
	//}
        
}
set_error_handler("test_failure");

/* basic functionality testing.  could be much improved
   with something like PHPunit
*/
require_once('./Instrumentation.php');
echo "This script uses the default mysql connection settings from the php.ini file.\n";
echo "The only query which will be executed is 'SELECT 1'\n\n";
echo "-- BEGIN --\n";
$instance = false;
$instance = Instrumentation::get_instance();
assert($instance !== false);
$instance->start_request();

echo " * Test setting a counter to a value\n";
$instance->set('test_counter', 'abc');
assert($instance->get('test_counter') == 'abc');

echo "* Incrementing an non-existing counter sets the counter to 1\n";
$instance->increment('empty_counter');
assert($instance->get('empty_counter') == 1);

echo "* Test setting to empty string and appending a string\n";
$instance->set('test_counter', '');
$instance->append('test_counter', 'abc');
assert($instance->get('test_counter') == 'abc');

echo "* Test the timer functionality\n";
$instance->timer();
sleep(1);
assert($instance->timer() > 1.0 && $instance->timer() < 2.0);

/* Reset all counters*/
$instance->reset();

echo "* Test the mysqli functional interface\n";
$conn = false;
$conn = MySQLi_perf::mysqli_connect('127.0.0.1','root');
assert($conn !== false);
assert($instance->get('mysql_connect_time') > 0);
$r = MySQLi_perf::mysqli_query($conn,'select 1');
assert($r);
assert($instance->get('mysql_query_exec_time') > 0);

$instance->reset();

echo "* Test the mysqli OO interface\n";
$conn = false;
$conn = new MySQLi_perf('127.0.0.1','root');
assert($conn !== false);
assert($instance->get('mysql_connect_time') > 0);
$rows = $conn->query('select 1');
assert($instance->get('mysql_query_exec_time') > 0);

$instance->reset();
$conn = false;

echo "* Test the legacy MySQL interface\n";
$conn = MySQL_perf::mysql_connect('127.0.0.1','root');
assert($conn !== false);
assert($instance->get('mysql_connect_time') > 0);
$stmt = MySQL_perf::mysql_query('select 1', $conn);
assert($instance->get('mysql_query_exec_time') > 0);
/* Test that the counter is only incremented when a new connection is made.*/
$stmt = MySQL_perf::mysql_connect('127.0.0.1','root');
assert($instance->get('mysql_connection_count') == 1);
$stmt = MySQL_perf::mysql_connect('localhost','root');
assert($instance->get('mysql_connection_count') == 2);

echo $instance->dump_counters('console') . "\n";
echo "Done.  All tests passed.";
