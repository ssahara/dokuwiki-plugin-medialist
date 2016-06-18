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
     *
     * replace medialst placeholders in xhtml of the page
     */
    public function handlePostProcess(Doku_Event $event, $param) {

        $pattern = '#<!-- MEDIALIST:([^\r\n]+?) -->#';

        if ($event->data[0] == 'xhtml') {
            // regular expression search and replace using anonymous function callback
            $event->data[1] = preg_replace_callback( $pattern,
                function ($matches) {
                    $medialist = $this->loadHelper('medialist');
                    $data = '{{medialist>'.$matches[1].'}}';
                    $params = $medialist->parse($data);
                    return $medialist->render_xhtml($params);
                },
                $event->data[1]
            );
        }

    }

}

