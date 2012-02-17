<?php

namespace Redline\Database\Migration;

interface SqlGeneratorInterface
{
    function createTableHead($name, $ifnotexists = false);
    
    function createTableAddColumn($name, array $options);
    
    function createTableAddIndex($name, array $options);
    
    function createTableTail(array $options);
    
    function createIndex($table, $name, array $options);
    
    function dropTable($name, array $options);
    
    function dropIndex($name, array $options);
    
    function alterTableRename($oldName, $newName);
    
    function alterTableHead($name);
    
    function alterTableAddColumn($name, array $options);
    
    function alterTableAlterColumn($name, array $options);
    
    function alterTableRenameColumn($oldName, $newName);
    
    function alterTableAddIndex(array $options);
    
    function alterTableDropIndex($name);
    
    function alterTableTail();
}