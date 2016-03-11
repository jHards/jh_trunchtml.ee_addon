<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * JH TruncHTML Class
 *
 * @package      ExpressionEngine
 * @category     Plugin
 * @author       jHards - Jonathan Hardisty
 * @copyright    Copyright (c) 2016, Jonathan Hardisty
 * @link         https://github.com/jHards/jh_trunchtml.ee_addon
 */

class Jhtrunchtml {
    
    public static $name         = 'JH TruncHTML';
    public static $version      = '1.0';
    public static $author       = 'jHards';
    public static $author_url   = 'https://github.com/jHards';
    public static $description  = 'A HTML aware way to truncates HTML/Text after a specified number of characters. Thanks to Oliver Heine for providing TruncHTML. I needed one for EE3 and reproduced the same functionality from it since it was let go.';
    public static $typography   = FALSE;
    
    public $return_data = "";
    
    /*
     * JH TruncHTML
     *
     * This is a reitteration of Oliver Heine's TruncHTML
     *
     * @access public
     * @return string
     */
    
    public function __construct() {
        
        $text = ee()->TMPL->tagdata;
        
        $threshold = ee()->TMPL->fetch_param('threshold', '0');
        $chars = ee()->TMPL->fetch_param('chars','500');
        $ending = ee()->TMPL->fetch_param('ending','');
        $exact = ee()->TMPL->fetch_param('exact','no');
        $inline = ee()->TMPL->fetch_param('inline','');
        
        $raw = strlen(preg_replace('/<.*?>/', '', $text));

        // Does raw text meet character limit?
        if ( $raw <= $chars)
        {
            $this->return_data = $text;
            return;
        }

        // Does raw text meet threshold limit?
        if ( $threshold > 0 && $raw < $threshold )
        {
            $this->return_data = $text;
            return;
        }

        preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
        $total_length = 0;
        $open_tags = array();
        $truncate = '';
        foreach ($lines as $line_matchings)
        {
            if (!empty($line_matchings[1]))
            {
                if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1]))
                {
                }
                elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings))
                {
                    $pos = array_search($tag_matchings[1], $open_tags);
                    if ($pos !== false)
                    {
                        unset($open_tags[$pos]);
                    }
                }
                elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings))
                {
                    array_unshift($open_tags, strtolower($tag_matchings[1]));
                }
                $truncate .= $line_matchings[1];
            }
            $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
            if ($total_length + $content_length > $chars)
            {
                $left = $chars - $total_length;
                $entities_length = 0;
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE|PREG_PATTERN_ORDER))
                {
                    foreach ($entities[0] as $entity)
                    {
                        if ($entity[1] + 1 - $entities_length <= $left)
                        {
                            $left--;
                            $entities_length += strlen($entity[0]);
                        }
                        else
                        {
                            break;
                        }
                    }
                }
                $truncate .= substr($line_matchings[2], 0, $left + $entities_length);

                break;
            }
            else
            {
                $truncate .= $line_matchings[2];
                $total_length += $content_length;
            }
            if ($total_length >= $chars)
            {
                break;
            }
        }

        if ($exact != "yes")
        {
            $last_gt = strrpos($truncate, '>');
            $spacepos = strrpos($truncate, ' ');
            if ( ($last_gt !== FALSE && $spacepos !== FALSE) && $last_gt > $spacepos )
            {
                $spacepos = strrpos($truncate, '<');
                if ($spacepos !== FALSE)
                {
                    $truncate = substr($truncate, 0, $spacepos);
                    array_shift($open_tags);
                }
            }
            elseif ( $spacepos !== FALSE )
            {
                $truncate = substr($truncate, 0, $spacepos);
            }
        }

        $truncate = rtrim($truncate);

        if (!empty($inline))
        {
            if (substr($inline,0,1)=="_")
            {
                $inline = " ".ltrim($inline,"_");
            }
            $truncate .= $inline;
        }

        foreach ($open_tags as $tag)
        {
            $truncate .= '</' . $tag . '>';
        }

        if ( !empty($ending) )
        {
            $truncate .= $ending;
        }

        $this->return_data = $truncate;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Usage
     * 
     * This function describes how the plugin is used.
     * 
     * @access public
     * @return string
     */
    
    public static function usage()
    {
        ob_start();
        ?>
        
    Example:
    ----------------
    {exp:jhtrunchtml
        chars="300"
        inline="..."
        ending="<a href='{path=site/comments}'>read on</a>"
    }
        {body}
    {/exp:jhtrunchtml}
    
    Parameters:
    ----------------
    chars=""
    Defaults to 500. Number of characters that are to be returned.
    
    ending=""
    Optional. String to be added after the output.
    
    inline=""
    Optional. This string is placed directly _after_ the truncated
    text and _before_ any closing tags.
    If you want the first character to be a space, use an underscore
    e.g. inline="_continue"
    
    exact="yes"
    If this is set, text will be truncated after exactly the specified
    number of chars. Otherwise text will be cut after a space to prevent
    cutting words in the middle.
    
    threshold="X"
    If this is set the text will only be truncated if it at least X characters long.
    Otherwise the full text is returned.
    
    ----------------
    CHANGELOG:
    
    1.0
    * 1st version for EE 3.0

        <?php
        $buffer = ob_get_contents();
        ob_end_clean();
        
        return $buffer;
    }
}
/* End of file pi.jhtrunchtml.php */
/* Location: ./system/user/addons/jhtrunchtml/pi.jhtrunchtml.php */
