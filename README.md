# Jh\CoreBugSalesRuleUpgrade Module

## Overview
This module was created to fix an issue where Magento would throw the following error
```
Module 'Magento_SalesRule':
Upgrading data..
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '0-0-0-0' for key 'PRIMARY', query was: INSERT INTO `salesrule_product_attribute` () VALUES ()
```
while upgrading the data of the Magento_SalesRule Module in Magento 2.2.

#### How it works:

- Applies Magento patch to wrap `$connection->insertMultiple($this->getTable('salesrule_product_attribute'), $data);` in a conditional to ensure `$data` is not null/empty.

#### Installation

Via Composer
```
composer require wearejh/m2-core-bug-sales-rule-upgrade
```

Enable the module
```
bin/magento module:enable Jh_CoreBugSalesRuleUpgrade
```
