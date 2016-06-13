<?php
/**
 * DokuWiki Syntax Plugin Medialist
 *
 * Show a list of media files (images/archives ...) referred in a given page
 * or stored in a given namespace.
 *
 * Syntax:  {{medialist>[id]}}
 *          {{medialist>[ns]:}} or {{medialist>[ns]:*}}
 *
 *   [id] - a valid page id (use @ID@ for the current page)
 *   [ns] - a namespace (use @NS@: for the current namespace)
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Michael Klier <chi@chimeric.de>
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_medialist extends DokuWiki_Syntax_Plugin {

    function getType()  { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort()  { return 299; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{medialist>[^\r\n]+?}}',$mode,'plugin_medialist');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        global $ID;

        // catch the match
        $match = substr($match, 12, -2);

        // process the match
        $params = array();

        // v1 syntax (backword compatibility for 2009-05-21 release)
        // @PAGE@, @NAMESPACE@, @ALL@ are complete keyword arguments,
        // not replacement patterns.
        switch ($match) {
            case '@PAGE@':
                $params = array('scope' => 'page', 'id' => $ID );
                break;
            case '@NAMESPACE@':
                $params = array('scope' => 'ns',   'id' => getNS($ID) );
                break;
            case '@ALL@':
            case '@BOTH@':
                $params = array('scope' => 'both', 'id' => $ID );
                break;
        }

        // v2 syntax (available since 2016-06-XX release)
        // - enable replacement patterns @ID@, @NS@, @PAGE@
        //   for media file search scope
        // - Namespace search if scope parameter ends colon ":", and
        //   require "*" after the colon for recursive search
        if (empty($params)) {
            $target = trim($match);

            // namespace searach options
            if (substr($target, -2) == ':*') {
                $params['scope']  = 'ns';  // not set depth option
            } elseif (substr($target, -1) == ':') {
                $params['scope']  = 'ns';
                $params['depth'] = 1;
            } else {
                $params['scope']  = 'page';
            }
            $target = rtrim($target, ':*');

            // replacement patterns identical with Namespace Template
            // @see https://www.dokuwiki.org/namespace_templates#syntax
            $target = str_replace('@ID@', $ID, $target);
            $target = str_replace('@NS@', getNS($ID), $target);
            $target = str_replace('@PAGE@', noNS($ID), $target);

            $params['id'] = cleanID($target);
        }

        return array($state, $params);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {

        list($state, $params) = $data;

        switch ($format) {
            case 'xhtml':
                // disable caching
                $renderer->info['cache'] = false;
                $medialist = $this->loadHelper('medialist');
                $renderer->doc .= $medialist->render_xhtml($params);
                return true;
        }
        return false;
    }

}
