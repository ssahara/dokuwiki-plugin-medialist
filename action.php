<?php
/**
 * DokuWiki Action Plugin Medialist
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class action_plugin_medialist extends DokuWiki_Action_Plugin {

    /**
     * Register event handlers
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook(
            'RENDERER_CONTENT_POSTPROCESS', 'BEFORE',
             $this, 'handlePostProcess', array()
        );
    }


    /**
     * handler of content postprocess
     */
    public function handlePostProcess(Doku_Event $event, $param) {

         // replace PLACEHOLDER
         if (strpos($event->data[1], '<!-- MEDIALIST -->') !== false) {
             $html = $this->_medialist(); // medialist xhtml content

             $event->data[1] = str_replace('<!-- MEDIALIST -->', $html, $event->data[1]);
         }
    }

    /**
     * create xhtml of medialist
     */
    private function _medialist() {

        $out = '';

            $out .= '<div class="medialist info">';
            $out .= '<strong>'.$this->getPluginName().'</strong>'.': nothing to show here.';
            $out .= '</div>';
        
        return $out;
    }

}

