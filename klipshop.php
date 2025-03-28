<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class KlipShop extends Module
{
    public function __construct()
    {
        $this->name = 'klipshop';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Dainius, Vilius, Linas';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();


        $this->description = $this->trans('Sharing cart module created by Dainius, Vilius and Linas', [], 'Modules.KlipShop.Admin');
    }

    public function install()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sharecart_links` (
        `id_sharecart` INT AUTO_INCREMENT PRIMARY KEY,
        `cart_id` INT NOT NULL,
        `token` VARCHAR(32) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        return parent::install()
            && Db::getInstance()->execute($sql)
            && $this->registerHook('displayExpressCheckout');
    }


    public function uninstall()
    {
        return parent::uninstall()
            && $this->unregisterHook('displayExpressCheckout')
            && Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'sharecart_links`');
    }

    public function hookDisplayExpressCheckout($params)
    {
        // Paimam esamo krepselio id
        $cartId = (int) $this->context->cart->id;

        // tikrinam ar sis krepselis turi tokena dalinimuisi
        $existingToken = Db::getInstance()->getValue(
            'SELECT token FROM '._DB_PREFIX_.'sharecart_links WHERE cart_id = '.$cartId
        );


        if (!$existingToken) {
            $token = Tools::passwdGen(15);
            Db::getInstance()->insert('sharecart_links', [
                'cart_id' => $cartId,
                'token' => pSQL($token),
            ]);
        } else {
            $token = $existingToken;
        }

        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'sharecart_links WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)'
        );

        // priskiriam linka templeitui
        $link = $this->context->link->getModuleLink('klipshop', 'cart', ['token' => $token]);
        $this->context->smarty->assign('share_cart_link', $link);

        return $this->display(__FILE__, 'views/templates/cart.tpl');
    }

}
