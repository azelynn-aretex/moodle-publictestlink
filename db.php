<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_publictestlink_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2024111400) {
        // Define table local_publictestlink to be created
        $table = new xmldb_table('local_publictestlink');
        
        // Adding fields to table local_publictestlink
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ispublic', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('hash', XMLDB_TYPE_CHAR, '32', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        // Adding keys to table local_publictestlink
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('uq_quizid', XMLDB_KEY_UNIQUE, array('quizid'));
        $table->add_key('uq_hash', XMLDB_KEY_UNIQUE, array('hash'));
        
        // Adding indexes to table local_publictestlink
        $table->add_index('idx_ispublic', XMLDB_INDEX_NOTUNIQUE, array('ispublic'));
        $table->add_index('idx_quizid_ispublic', XMLDB_INDEX_NOTUNIQUE, array('quizid', 'ispublic'));
        
        // Conditionally launch create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Publictestlink savepoint reached
        upgrade_plugin_savepoint(true, 2024111400, 'local', 'publictestlink');
    }
    
    return true;
}