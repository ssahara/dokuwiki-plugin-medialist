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
         global $INFO;

         // replace PLACEHOLDER
         if (strpos($event->data[1], '<!-- MEDIALIST -->') !== false) {
             if (isset($INFO['meta']['plugin_medialist']['params'])) {
                 $params = $INFO['meta']['plugin_medialist']['params'];
                 $medialist = $this->loadHelper('medialist');
                 $html = $medialist->render_xhtml($params); // medialist xhtml content
                 $event->data[1] = str_replace('<!-- MEDIALIST -->', $html, $event->data[1]);
             }
         }
    }

}

