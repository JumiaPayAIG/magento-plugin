JumiaPay Magento Integration
=========================

The official JumiaPay extension for Magento 2.

Install
=======

### Magento Marketplace

TBD

### Manually

1. Go to Magento 2 root folder

2. Enter following commands to install module:

    ```bash
    composer config repositories.jumiapay git https://github.com/JumiaPayAIG/magento-plugin
    composer require JumiaPayAIG/magento-plugin:dev-master
    ```
   Wait while dependencies are updated.

3. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable Jpay_Payments --clear-static-content
    php bin/magento setup:upgrade
    ```
4. Enable and configure JumiaPay in Magento Admin
