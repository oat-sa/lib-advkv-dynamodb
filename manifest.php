<?php
/**  
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *               
 * 
 */               

return array(
    'name' => 'kvDynamoDb',
	'label' => 'Key-Value DynamoDb',
	'description' => 'An advanced Key-Value peristence implementation for DynamoDb',
    'license' => 'GPL-2.0',
    'version' => '0.1',
	'author' => 'Open Assessment Technologies SA',
	'requires' => array('tao' => '>=2.6'),
	// for compatibility
	'dependencies' => array('tao'),
	'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#KvDynamoDbManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#KvDynamoDbManager', array('ext'=>'kvDynamoDb')),
    ),
    'uninstall' => array(
    ),
    'autoload' => array (
        'psr-4' => array(
            'oat\\kvDynamoDb\\' => dirname(__FILE__).DIRECTORY_SEPARATOR
        ),
        'files' => array(
        	dirname(__FILE__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php'
        )
    ),  
	'constants' => array(
	)
);