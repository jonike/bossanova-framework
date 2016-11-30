<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Model
 */
namespace models;

use Bossanova\Model\Model;

class Audit extends Model
{
    // Table configuration
    public $config = array(
        'tableName' => 'audit',
        'primaryKey' => 'audit_id',
        'sequence' => 'audit_audit_id_seq',
        'recordId' => 0
    );
}
