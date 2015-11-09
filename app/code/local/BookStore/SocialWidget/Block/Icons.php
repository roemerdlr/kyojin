<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class BookStore_SocialWidget_Block_Icons extends 
Mage_Core_Block_Abstract implements
Mage_Widget_Block_Interface{
    protected function _toHtml() {
        $html='<!-- AddThis Button BEGIN -->'
                . '<div class="addthis_toolbook addthis_default_style addthis_32x32_style">'
                . '<a class="addthis_button_facebook"></a>'
                . '<a class="addthis_button_twitter"></a>'
                . '<a class="addthis_button_compact"></a>'
                . '</div>'
                . '<script type="text/javascript" '
                . 'src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ax-52cae78918520295"></script>';
        return $html;
    }
}