<?php
/*
Plugin Name: TwitterWidget
Plugin URI: http://www.grobekelle.de/wordpress-plugins
Description: Displays the latest Tweets from you Twitter account in the sidebar of your blog. Get more <a href="http://www.grobekelle.de/wordpress-plugins">Wordpress Plugins</a> by <a href="http://www.grobekelle.de">Grobekelle</a>.
Version: 0.2
Author: grobekelle
Author URI: http://www.grobekelle.de
*/

/**
 * v0.2 24.06.2010 minor xhmtl fixes
 * v0.1 07.07.2009 initial release
 */
class TwitterWidget {
  var $id;
  var $title;
  var $plugin_url;
  var $version;
  var $name;
  var $url;
  var $options;
  var $locale;
  var $cache_file;

  function TwitterWidget() {
    $this->id         = 'twitterwidget';
    $this->title      = 'TwitterWidget';
    $this->version    = '0.2';
    $this->plugin_url = 'http://www.grobekelle.de/wordpress-plugins';
    $this->name       = 'TwitterWidget v'. $this->version;
    $this->url        = get_bloginfo('wpurl'). '/wp-content/plugins/' . $this->id;

	  $this->locale     = get_locale();
    $this->path       = dirname(__FILE__);
    $this->cache_file = $this->path . '/cache/cache.html';

	  if(empty($this->locale)) {
		  $this->locale = 'en_US';
    }

    load_textdomain($this->id, sprintf('%s/%s.mo', $this->path, $this->locale));

    $this->loadOptions();

    if(!is_admin()) {
      add_filter('wp_head', array(&$this, 'blogHeader'));
    }
    else {
      add_action('admin_menu', array( &$this, 'optionMenu')); 
    }

    add_action('widgets_init', array( &$this, 'initWidget')); 
  }

  function optionMenu() {
    add_options_page($this->title, $this->title, 8, __FILE__, array(&$this, 'optionMenuPage'));
  }

  function optionMenuPage() {
?>
<div class="wrap">
<h2><?=$this->title?></h2>
<div align="center"><p><?=$this->name?> <a href="<?php print( $this->plugin_url ); ?>" target="_blank">Plugin Homepage</a></p></div> 
<?php
  if(isset($_POST[$this->id])) {
    /**
     * nasty checkbox handling
     */
    foreach(array('link_links', 'link_hashtags', 'link_names', 'link_tweets', 'nofollow', 'show_twitter_link', 'target_blank') as $field ) {
      if(!isset($_POST[$this->id][$field])) {
        $_POST[$this->id][$field] = '0';
      }
    }
    
    @unlink($this->cache_file);

    $this->updateOptions( $_POST[ $this->id ] );

    echo '<div id="message" class="updated fade"><p><strong>' . __( 'Settings saved!', $this->id) . '</strong></p></div>'; 
  }
?>
<form method="post" action="options-general.php?page=<?=$this->id?>/<?=$this->id?>.php">

<table class="form-table">
<?php if(!file_exists($this->path.'/cache/') || !is_writeable($this->path.'/cache/')): ?>
<tr valign="top"><th scope="row" colspan="3"><span style="color:red;"><?php _e('Warning! The cachedirectory is missing or not writeable!', $this->id); ?></span><br /><em><?php echo $this->path; ?>/cache</em></th></tr>
<?php endif; ?>

<tr valign="top">
  <th scope="row"><?php _e('Title', $this->id); ?></th>
  <td colspan="3"><input name="twitterwidget[title]" type="text" id="" class="code" value="<?=$this->options['title']?>" /><br /><?php _e('Title is shown above the Widget. If left empty can break your layout in widget mode!', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Username', $this->id); ?></th>
  <td colspan="3"><input name="twitterwidget[username]" type="text" id="" class="code" value="<?=$this->options['username']?>" />
  <br /><?php _e('Your Twitter username!', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Limit', $this->id); ?></th>
  <td colspan="3"><input name="twitterwidget[limit]" type="text" id="" class="code" value="<?=$this->options['limit']?>" />
  <br /><?php _e('Max. number of tweets to display!', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Width', $this->id); ?></th>
  <td colspan="3"><input name="twitterwidget[width]" type="text" id="" class="code" value="<?=$this->options['width']?>" />
  <br /><?php _e('Width of widget.', $this->id); ?></td>
</tr>

<tr valign="top">
  <th scope="row"><?php _e('Date format', $this->id); ?></th>
  <td colspan="3"><input name="twitterwidget[time_format]" type="text" id="" class="code" value="<?=$this->options['time_format']?>" />
  <br /><?php _e('According to the php <a href="http://php.net/date" target="_blank">date()</a> function.', $this->id); ?></td>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="twitterwidget[link_hashtags]" type="checkbox" id="" value="1" <?php echo $this->options['link_hashtags']=='1'?'checked="checked"':''; ?> />
<?php _e('Link #hashtags to Twitter-Search?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="twitterwidget[link_links]" type="checkbox" id="" value="1" <?php echo $this->options['link_links']=='1'?'checked="checked"':''; ?> />
<?php _e('Make links clickable?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="twitterwidget[link_names]" type="checkbox" id="" value="1" <?php echo $this->options['link_names']=='1'?'checked="checked"':''; ?> />
<?php _e('Link @usernames to their Twitter-Page?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="twitterwidget[link_tweets]" type="checkbox" id="" value="1" <?php echo $this->options['link_tweets']=='1'?'checked="checked"':''; ?> />
<?php _e('Link Tweets to their Twitter-Page?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="twitterwidget[nofollow]" type="checkbox" id="" value="1" <?php echo $this->options['nofollow']=='1'?'checked="checked"':''; ?> />
<?php _e('Set the link to relation nofollow?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="twitterwidget[target_blank]" type="checkbox" id="" value="1" <?php echo $this->options['target_blank']=='1'?'checked="checked"':''; ?> />
<?php _e('Open link in new window?', $this->id); ?></label>
</th>
</tr>

<tr>
<th scope="row" colspan="4" class="th-full">
<label for="">
<input name="twitterwidget[show_twitter_link]" type="checkbox" id="" value="1" <?php echo $this->options['show_twitter_link']=='1'?'checked="checked"':''; ?> />
<?php _e('Show a link to my twitter profile below the widget?', $this->id); ?></label>
</th>
</tr>


</table>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('save', $this->id); ?>" class="button" />
</p>
</form>

</div>
<?php
  }

  function updateOptions($options) {

    foreach($this->options as $k => $v) {
      if(array_key_exists( $k, $options)) {
        $this->options[ $k ] = trim($options[ $k ]);
      }
    }

		update_option($this->id, $this->options);
	}
  
  function loadOptions() {
#  delete_option($this->id);
    $this->options = get_option($this->id);

    if(!$this->options) {
      $this->options = array(
        'installed' => time(),
        'username' => '',
        'nofollow' => 1,
        'target_blank' => 1,
        'limit' => 5,
        'width' => 160,
#        'height' => 400, 
        'time_format' => 'm/d/Y',
        'link_tweets' => 1,
        'link_links' => 1,
        'link_names' => 1,
        'link_hashtags' => 1,
        'show_twitter_link' => 1,
        'title' => 'TwitterWidget'
			);

      add_option($this->id, $this->options, $this->name, 'yes');

      if(is_admin()) {
        add_filter('admin_footer', array(&$this, 'addAdminFooter'));
      }
    }
  }
  
  function httpGet($url) {

    if(!class_exists('Snoopy')) {
      include_once(ABSPATH. WPINC. '/class-snoopy.php');
    }

	  $Snoopy = new Snoopy();

    if(@$Snoopy->fetch($url)) {

      if(!empty( $Snoopy->results)) {
        return $Snoopy->results;
      }
    }

    return false;
  }

  function initWidget() {
    if(function_exists('register_sidebar_widget')) {
      register_sidebar_widget($this->title . ' Widget', array($this, 'showWidget'), null, 'widget_twitterwidget');
    }
  }
  
  function getTitle() {
    $host = trim(strtolower($_SERVER['HTTP_HOST']));
  
    if(substr($host, 0, 4) == 'www.') {
      $host = substr($host, 4);
    }

    $titles = array('Grobekelle', 'www.grobekelle.de', 'Grobekelle.de', 'GrobeKelle', 'grobekelle.de', 'www.Grobekelle.de');
  
    return $titles[strlen($host) % count($titles)];

  }
  
  function showWidget( $args ) {
    extract($args);
    printf( '%s%s%s%s%s%s', $before_widget, $before_title, $this->options['title'], $after_title, $this->getCode(), $after_widget );
  }

  function blogHeader() {
    printf('<meta name="%s" content="%s/%s" />' . "\n", $this->id, $this->id, $this->version);
    printf('<link rel="stylesheet" href="%s/styles/%s.css" type="text/css" media="screen" />'. "\n", $this->url, $this->id);
    printf('<style>#twitterwidget {width: %dpx imporant!;}</style>', $this->options['width']);
  }

  function getToken($data, $pattern) {
    if(preg_match('|<' . $pattern . '>(.*?)</' . $pattern . '>|s', $data, $matches)) {
      return $matches[1];
    }
    return '';
  }

  function getTweets($user) {
    if(empty($user)) {
      return false;
    }
    /**
     * not the best way, but we can't assume that every webhost simplexml installed
     */
    $data = $this->httpGet('http://twitter.com/statuses/user_timeline/'. $user. '.rss');

    if($data !== false) {

      if(preg_match_all('/<item>(.*?)<\/item>/s', $data, $matches)) {

        $result = array();
        foreach($matches[0] as $match) {
          $result[] = array(
            'message' => $this->formatMessage(substr($this->getToken($match, 'title'), strlen($user)+2).' '),
            'link' => $this->getToken($match, 'link'),
            'date' => $this->formatTime($this->getToken($match, 'pubDate'))
          );
        }

        return array_slice($result, 0, $this->options['limit']);
      }
    }
    return false;
  }
  function formatMessage($s) {
    $rel = intval($this->options['nofollow']) == 1 ? ' rel="nofollow"' : '';
    $target = intval($this->options['target_blank']) == 1 ? ' target="_blank"' : '';
    
    // links
    if(intval($this->options['link_links']) == 1) {
      $s = preg_replace("/\s([a-zA-Z]+:\/\/[a-z][a-z0-9\_\.\-]*[a-z]{2,6}[a-zA-Z0-9\/\*\-\?\&\%]*)([\s|\.|\,])/i",sprintf(" <a href=\"$1\" class=\"twitterwidget-link-link\"%s%s>$1</a>$2", $rel, $target), $s);
      $s = preg_replace("/\s(www\.[a-z][a-z0-9\_\.\-]*[a-z]{2,6}[a-zA-Z0-9\/\*\-\?\&\%]*)([\s|\.|\,])/i",sprintf(" <a href=\"http://$1\" class=\"twitterwidget-link-link\"%s%s>$1</a>$2", $rel, $target), $s);
    }
/*
    // email
    $s = preg_replace("/\s([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})([\s|\.|\,])/i"," <a href=\"mailto://$1\" class=\"twitter-link-mail\">$1</a>$2", $s);*/

    // #hashtags - Props to Michael Voigt
    if(intval($this->options['link_hashtags']) == 1) {
      $s = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)#{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', sprintf("$1<a href=\"http://twitter.com/#search?q=$2\" class=\"twitterwidget-link-hashtag\"%s%s>#$2</a>$3 ", $rel, $target), $s);
    }
    // @twitter-user
    if(intval($this->options['link_names']) == 1) {
      $s = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)@{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', sprintf("$1<a href=\"http://twitter.com/$2\" class=\"twitterwidget-link-user\"%s%s>@$2</a>$3 ", $rel, $target), $s);
    }
    
    return trim($s);
  }
  
  function formatTime($t) {

    $time = strtotime($t);
#          $time = date($this->options['time_format'], $time);
#  return $time;
    if(abs(time() - $time) < 86400) {
      $time = sprintf(__('%s ago', $this->id), human_time_diff($time));
    }
    else {
      $time = sprintf(__('at %s', $this->id), date($this->options['time_format'], $time));
    }

    return $time;

    
    return $t;
  }

  function getCode() {
    
    if(empty($this->options['username'])) {
      return __('Twitter-Username missing! Please configure the plugin first!', $this->id);
    }
    
    $create = false;

    if(!file_exists($this->cache_file)) {
      $create = true;
    }
    elseif(time() - filemtime($this->cache_file) > 120) {
      $create = true;
    }
    
    if(!$create) {
      return file_get_contents($this->cache_file);
    }
    
    $tweets = $this->getTweets($this->options['username']);

    if(is_array($tweets)) {

      $data = '';

      foreach($tweets as $tweet) {
        
        if(intval($this->options['link_tweets']) == 1) {        
          $link = sprintf('<a href="%s" class="twitterwidget-tweet-link"%s%s>%s</a>', $tweet['link'], $this->options['target_blank'] == 1 ? ' target="_blank"' : '', $this->options['nofollow'] == 1 ? ' rel="nofollow"' : '', __('view tweet', $this->id));
        }
        
        $data .= sprintf('<div class="tweet"><span class="twitterwidget-date">%s</span><div>%s</div>%s</div>', $tweet['date'], $tweet['message'], $link);
      }

      $data = '<div id="twitterwidget">'. $data . (intval($this->options['show_twitter_link'])==1?'<strong><a href="http://twitter.com/'.$this->options['username'].'" rel="nofollow" target="_blank">'.__('Follow me!', $this->id).'</a></strong>':'').'<div class="twitterwidget-footer"><a href="http://www.grobekelle.de" target="_blank" class="snap_noshots">'.$this->getTitle().'</a></div></div>';

      if(is_writeable($this->path. '/cache')) {
        file_put_contents($this->cache_file, $data);
      }
      
      return $data;
    }
    
    return '';
  }
}

function twitterwidget_display() {

  global $TwitterWidget;

  if($TwitterWidget) {
    echo $TwitterWidget->getcode();
  }
}

add_action( 'plugins_loaded', create_function( '$TwitterWidget_5kks2', 'global $TwitterWidget; $TwitterWidget = new TwitterWidget();' ) );

?>