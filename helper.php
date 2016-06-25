<?php
/**
 * Helper Component of Medialist plugin
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_medialist extends DokuWiki_Plugin {

    /**
     * syntax parser
     *
     * @param $data string matched the regex {{medialist>[^\r\n]+?}}
     * @return array parameter for render process
     */
    public function parse($data) {
        global $ID;

        $match = substr($data, 12, -2);
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
                $params['depth']  = 1;
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
        return $params;
   }


    /**
     * Renders xhtml
     */
   public function render_xhtml($params) {

        $scope = $params['scope'];
        $id    = $params['id'];

        // search option for lookup_stored_media()
        if (array_key_exists('depth', $params)) {
            $opt = array('depth' => $params['depth']);
        } else {
            $opt = array();
        }

        // prepare list items
        $items = array();
        switch ($scope) {
            case 'page':
                $media = $this->_lookup_linked_media($id);
                foreach ($media as $item) {
                    $items[] = $item + array('level' => 1, 'base' => getNS($item['id']));
                }
                break;
            case 'ns':
                $ns = $id;
                $media = $this->_lookup_stored_media($ns, $opt);
                foreach ($media as $item) {
                    $items[] = $item + array('level' => 1, 'base' => $id);
                }
                break;
            case 'both':
                $ns = getNS($id);
                $linked_media = $this->_lookup_linked_media($id);
                $stored_media = $this->_lookup_stored_media($ns, $opt);
                $media = array_unique(array_merge($stored_media, $linked_media), SORT_REGULAR);

                foreach ($media as $item) {
                    if (in_array($item, $linked_media)) {
                        $item = $item + array('level' => 1, 'base' => $id, 'linked'=> 1);
                    } else {
                        $item = $item + array('level' => 1, 'base' => $id);
                    }
                    $items[] = $item;
                }
                break;
        }

        // create output
        $out  = '';
        $out .= '<div class="medialist">'. DOKU_LF;
        if (!empty($items)) {
            // mediamanager button
            if (isset($ns) && (auth_quickaclcheck("$ns:*") >= AUTH_DELETE)) {
                $out .= '<div class="mediamanager">';
                $out .= $this->_mediamanager_button($ns);
                $out .= '</div>'. DOKU_LF;
            }
            // list
            $out .= html_buildlist($items, 'medialist', array($this, '_media_item'));
            $out .= DOKU_LF;
        } else {
            $out .= '<div class="info">';
            $out .= '<strong>'.$this->getPluginName().'</strong>'.': nothing to show here.';
            $out .= '</div>'. DOKU_LF;;
        }
        $out .= '</div>'. DOKU_LF;
        return $out;
    }

    /**
     * Callback function for html_buildlist()
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    public function _media_item($item) {
        global $conf, $lang;

        $out = '';

        $link = array();
        $link['url']    = ml($item['id']);
        $link['class']  = isset($item['linked']) ? 'media linked' : 'media';
        $link['target'] = $conf['target']['media'];
        $link['title']  = noNS($item['id']);

        // link text and mediainfo
        if ($item['type'] == 'internalmedia') {
            // Internal file
            if (array_key_exists('base', $item)) {
                $link['name'] = str_replace($item['base'].':','', $item['id']);
            } else {
                $link['name'] = $item['id'];
            }
            $mediainfo  = strftime($conf['dformat'], $item['mtime']).'&nbsp;';
            $mediainfo .= filesize_h($item['size']);
        } else {
            // External link
            $link['name'] = $item['id'];
            $mediainfo = $lang['qb_extlink']; // External Link
        }

        // add file icons
        list($ext,$mime) = mimetype($item['id']);
        $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
        $link['class'] .= ' mediafile mf_'.$class;

        // build the list item
        $out .= '<input type="checkbox" id="delete['.$item['id'].']" />';
        $out .= '<label for="delete['.$item['id'].']">'.'</label>';
        $out .= '<a href="' . $link['url'] . '" ';
        $out .= 'class="' . $link['class'] . '" ';
        $out .= 'target="' . $link['target'] . '" ';
        $out .= 'title="' . $link['title'] . '">';
        $out .= $link['name'];
        $out .= '</a>';
        $out .= '&nbsp;<span class="mediainfo">('.$mediainfo.')</span>' . DOKU_LF;

        return $out;
    }

    /**
     * button to open a given namespace with the Fullscreen Media Manager
     */
    protected function _mediamanager_button($ns) {
        global $ID, $lang;

        $params  = array('do' => 'media', 'ns' => $ns);
        $method  = 'get';
        $label   = hsc("$ns:*");
        $tooltip = $lang['btn_media'];
        return html_btn('media', $ID, $accesskey, $params, $method, $tooltip, $label);
    }


    /**
     * searches media files linked in the given page
     * returns an array of items
     */
    protected function _lookup_linked_media($id) {
        $linked_media = array();

        if (!page_exists($id)) {
            //msg('MediaList: page "'. hsc($id) . '" not exists!', -1); 
        }

        if (auth_quickaclcheck($id) >= AUTH_READ) {
            // get the instructions
            $ins = p_cached_instructions(wikiFN($id), true, $id);

            // get linked media files
            foreach ($ins as $node) {
                if ($node[0] == 'internalmedia') {
                    $id = cleanID($node[1][0]);
                    $fn = mediaFN($id);
                    if (!file_exists($fn)) continue;
                    $linked_media[] = array(
                        'id'    => $id,
                        'size'  => filesize($fn),
                        'mtime' => filemtime($fn),
                        'type'  => $node[0],
                    );
                } elseif ($node[0] == 'externalmedia') {
                    $linked_media[] = array(
                        'id'    => $node[1][0],
                        'size'  => null,
                        'mtime' => null,
                        'type'  => $node[0],
                    );
                }
            }

        }
        return array_unique($linked_media, SORT_REGULAR);
    }

    /**
     * searches media files stored in the given namespace and sub-tiers
     * returns an array of items
     */
    protected function _lookup_stored_media($ns, $opt=array('depth'=>1)) {
        global $conf;

        $stored_media = array();

        $dir = utf8_encodeFN(str_replace(':','/', $ns));

        if (!is_dir($conf['mediadir'] . '/' . $dir)) {
            //msg('MediaList: namespace "'. hsc($ns). '" not exists!', -1);
        }

        if (auth_quickaclcheck("$ns:*") >= AUTH_READ) {
            // search media files in the namespace
            $res = array(); // search result
            search($res, $conf['mediadir'], 'search_media', $opt, $dir);

            // prepare return array
            foreach ($res as $item) {
                $stored_media[] = array(
                        'id'    => $item['id'],
                        'size'  => $item['size'],
                        'mtime' => $item['mtime'],
                        'type'  => 'internalmedia',
                );
            }
        }
        return $stored_media;
    }

}

