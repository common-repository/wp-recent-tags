<?php
/*
Plugin Name: WP Recent Tags
Plugin URI: http://www.mashget.com/2008/09/18/wp-recent-tags-for-wordpress/
Description: Show the recent tags.
Author: Andrew Zhang
Version: 0.1.1
Author URI: http://www.mashget.com
*/
if  (!class_exists('RecentTags')):
class RecentTags
{
	var $plugin_name="WP Recent Tags";
	var $plugin_version="0.1.1";
	var $plugin_uri="http://www.mashget.com";

	var $wptTable;
	var $post_tag_update_count_callback;
	var $tagcount_update_log=array();
	var $rtOptions;

	function RecentTags()
	{
		global $wpdb;
		$this->wptTable=$wpdb->prefix . 'wpt_recent_tagcount';
		$this->rtOptions=get_option('widget_recent_tags');
		if(!$this->rtOptions)
		{
			$this->rtOptions=$this->get_def_options();
		}
		$taxonomy = get_taxonomy('post_tag');		
		if (!empty($taxonomy->update_count_callback) ) 
		{
			$this->post_tag_update_count_callback=$taxonomy->update_count_callback;
			$taxonomy->update_count_callback=array(&$this,'post_tag_update_count');
			add_action('wp_insert_post', array(&$this,'check_tagcount_update_log'), PHP_INT_MAX, 2);
		}
		if($this->rtOptions['style_css_enabled'])
		{
			add_action('wp_head', array(&$this,'generate_rtstyle'));
		}
		
		add_action('delete_term', array(&$this,'deletetagstat'));
		add_action('widgets_init', array(&$this, 'register_wrt_widget' ));
		add_action('admin_menu', array(&$this,'add_options_page'));
	}
	
	function get_def_options()
	{
		return array(
				'title'=>'Recent Tags',
				'postsnum'=>10,
				'tagsnum'=>15,
				'style'=>'cloud',
				"style_css_enabled" => "1",
				"cloud_style_css" =>"ul.recent-tags li { display:inline; border:0; }
					ul.recent-tags li a { font-weight:400; line-height:120%; margin:0 0.5em 0 0; }
					ul.recent-tags li .S3 { font-size:13pt; }
					ul.recent-tags li .S2 { font-size:11pt; }
					ul.recent-tags li .S1 { font-size:8pt; }
					",
				"list_style_css" =>"ul.recent-tags li { border:0; }
					ul.recent-tags li a { font-weight:400; line-height:120%; margin:0 0.5em 0 0; }
					",
			);
	}
	
	function add_options_page()
	{
		if (function_exists('add_options_page'))
		{
			add_options_page( $this->plugin_name, $this->plugin_name, 8, basename(__FILE__), array(&$this,'wp_recent_tags_options_subpanel'));
		}
	}

	function wp_recent_tags_options_subpanel()
	{
		if($_POST["wp_rt_submit"])
		{
			$wp_settings = array (
				"style_css_enabled" => $_POST['style_css_enabled']? "1":false,
				"cloud_style_css" => $_POST['cloud_style_css'],
				"list_style_css" => $_POST['list_style_css'],
			);
			$wp_settings=array_merge($this->rtOptions, $wp_settings);
			update_option("widget_recent_tags",$wp_settings);
			echo '<div id="message" class="updated fade"><p>Options Updated</p></div>';
		}
		else if($_POST["wp_rt_load_default"])
		{
			$def=$this->get_def_options();
			$wp_settings = array (
				"cloud_style_css" => $def["cloud_style_css"],
				"list_style_css" => $def["list_style_css"],
			);
			$wp_settings=array_merge($this->rtOptions, $wp_settings);
			update_option("widget_recent_tags",$wp_settings);
			echo '<div id="message" class="updated fade"><p>Options Reset</p></div>';
		}
		else
		{
			$wp_settings=$this->rtOptions;
		}
		?>
		<div class="wrap">
		  <form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=recent-tags.php" method="post">
		    <h2><?php echo $this->plugin_name;?> Options</h2>
		    <table class="form-table">
		      <tr valign="top">
		        <td>        
		        <input name="style_css_enabled" type="checkbox" id="style_css_enabled" value="1" <?php checked('1', ($wp_settings["style_css_enabled"]==="1")); ?> />
		        <label for=style_css_enabled><strong>Output style in the page</strong></label> (Or you may want to put these in your own css) 
		        <p>
		        	<a name="rt-cloud"></a>
		        	<label>For Cloud</label>
		        	<br/>
		        	<textarea name="cloud_style_css" rows="5" cols="80"><?php echo str_replace("\t","",$wp_settings["cloud_style_css"]); ?></textarea>
		        </p>
		        <p>
		        	<a name="rt-list"></a>
		        	<label>For List</label>
		        	<br/>
		        	<textarea name="list_style_css" rows="5" cols="80"><?php echo str_replace("\t","",$wp_settings["list_style_css"]); ?></textarea>
		        </p>
		        </td>
		      </tr>
		    </table>
		    <p class="submit">
		      <input type="submit" name="wp_rt_load_default" value="Reset to Default Options &raquo;" class="button" onclick="return confirm('Are you sure to reset options?')" />
		      <input type="submit" name="wp_rt_submit" value="Save Options &raquo;" class="button" style="margin-left:15px;" />
		    </p>  
		  </form>
		</div>
		<?php
	}
	
	function register_wrt_widget()
	{
		if (!function_exists( 'register_sidebar_widget' ))return;
		register_sidebar_widget($this->plugin_name, array(&$this, 'generate_wrt_widget' ));
		register_widget_control($this->plugin_name, array(&$this, 'wrt_widget_control' ) );
	}

	function wrt_widget_control()
	{	
		$options = $newoptions = $this->rtOptions;
		if (isset($_POST['recent-tags-title'])) 
		{
			$newoptions['title'] =(stripslashes($_POST['recent-tags-title']));
			$newoptions['postsnum']=intval($_POST['recent-tags-rcposts-num']);
			$newoptions['tagsnum']=intval($_POST['recent-tags-maxtags-num']);
			$newoptions['style']=($_POST['recent-tags-style']);
		}
		if ( $options != $newoptions ) 
		{
			if($newoptions['postsnum'] > $options['postsnum'])
			{
				$this->preRtdata(intval($newoptions['postsnum']));
			}
			$options = $newoptions;
			update_option('widget_recent_tags', $options);
		}		
		
		$title = attribute_escape($options['title'] );
		$postsnum = $options['postsnum'];
		$tagsnum =$options['tagsnum'];
		
		if(!class_exists("WidgetCache"))
		{
			?>
			<p>
			<i>
			You might use <a href='http://wordpress.org/extend/plugins/wp-widget-cache/' target='_blank'>
			WP Widget Cache</a> 
			to improve performance</i>
			</p>
			<?php
		}
		?>
		<p>
		<label for="recent-tags-title">
		Title: <input type="text" id="recent-tags-title" name="recent-tags-title" value="<?php echo $title ?>" style="width: 200px;"/>
		</label>
		</p>
		<p>
		<label for="recent-tags-maxtags-num">
		Number of tags to show: <input type="text" size="2" id="recent-tags-maxtags-num" name="recent-tags-maxtags-num" value="<?php echo $tagsnum ?>"  style="text-align:center;"/>
		</label>
		</p>
		<p>
		<label for="recent-tags-rcposts-num">
		Show the tags in recent <input type="text" size="2" id="recent-tags-rcposts-num" name="recent-tags-rcposts-num" value="<?php echo $postsnum ?>" style="text-align:center;"/> posts
		</label> (<a href="http://www.mashget.com/2008/09/18/wp-recent-tags-for-wordpress/#recent" target="_blank">?</a>)
		</p>	
		<p>
		Output style: 
			<label for="recent-tags-style-cloud">
				<input type="radio" id="recent-tags-style-cloud" name="recent-tags-style" value="cloud" <?php checked('1', $options['style']=="cloud"); ?> /> Cloud
			</label> (<a href='/wp-admin/options-general.php?page=recent-tags.php#rt-cloud' target='_blank'>css</a>)
			<label for="recent-tags-style-list">
				<input type="radio" id="recent-tags-style-list" name="recent-tags-style" value="list" <?php checked('1', $options['style']=="list"); ?> /> List
			</label> (<a href='/wp-admin/options-general.php?page=recent-tags.php#rt-list' target='_blank'>css</a>)
		</p>
		<?php
	}

	function generate_rtstyle()
	{
		echo "<!--$this->plugin_name $this->plugin_version ($this->plugin_uri) Begin -->\n";
		echo "<style>\n";
		echo str_replace("\t","",$this->rtOptions[$this->rtOptions['style']."_style_css"]);
		echo "</style>\n";
		echo "<!--$this->plugin_name End -->\n";
	}
	
	function generate_wrt_widget($args)
	{
		extract($args);
		{
			$options = $this->rtOptions;
			
			$title = $options['title'];
			$postsnum =intval($options['postsnum']);	
			$tagsnum = intval($options['tagsnum']);
			
			if(!($postsnum>0&&$tagsnum>0))return;
			
			echo "<!--$this->plugin_name $this->plugin_version ($this->plugin_uri) Begin -->\n";
			echo $before_widget;
			echo $before_title.$title. $after_title;

			$sdate=$this->getRecentPostDate($postsnum);
			
			echo "<ul class='recent-tags'>";
			if($options['style']=='cloud')
			{	
				$rts=$this->getRecentTagCloud($tagsnum, $sdate, 3);	
				foreach ($rts as $lpterm)
				{
				?>
	            	<li><a class="S<?php echo $lpterm['slevel']; ?>" href="<?php echo $lpterm['link']; ?>" title="View all posts tagged with <?php echo $lpterm['name'];?>"><?php echo $lpterm['name'];?></a></li>
	            <?php
				}
				echo "</ul>";
			}
			else 
			{
				$rts=$this->getRecentTagNameLinks($tagsnum, $sdate);	
				echo "<ul class='recent-tags'>";
				foreach ($rts as $lpterm)
				{
				?>
	            	<li><a href="<?php echo $lpterm['link']; ?>" title="View all posts tagged with <?php echo $lpterm['name'];?>"><?php echo $lpterm['name'];?></a></li>
	            <?php
				}
			}
			echo "</ul>";
			echo $after_widget;
			echo "<!--$this->plugin_name End -->\n";
		}
	}

	function post_tag_update_count($terms)
	{
		global $wpdb;
		$msg="";
		foreach ( $terms as $term )
		{
			if(!$this->tagcount_update_log[$term])
			{
				$this->tagcount_update_log[$term]=array();

				$rec=$wpdb->get_row($wpdb->prepare("SELECT count, term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $term ));
				if(!$rec) continue;

				$oldcount=$rec->count;
				$term_id=$rec->term_id;
			}
			else
			{
				$oldcount=$this->tagcount_update_log[$term]['oldcount'];
				$term_id=$this->tagcount_update_log[$term]['term_id'];
			}

			$newcount = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type = 'post' AND term_taxonomy_id = %d", $term ) );
			//$wpdb->update($wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ));

			$this->tagcount_update_log[$term]=array(
			'oldcount'=>intval($oldcount),
			'newcount'=>intval($newcount),
			'term_id'=>intval($term_id)
			);
		}
		//call_user_func($this->post_tag_update_count_callback, $terms);
	}

	function check_tagcount_update_log($post_id=0, $post=null)
	{
		if(!($post_id > 0))return;
		if(!$post || 'publish' != $post->post_status)return;
		
		global $wpdb;
		foreach ($this->tagcount_update_log as $term_taxonomy_id=>$termArr)
		{
			if($termArr['oldcount']!=$termArr['newcount'])
			{
				$v=$wpdb->update($wpdb->term_taxonomy, array('count'=>$termArr['newcount']), array( 'term_taxonomy_id' => $term_taxonomy_id ));
				//file_put_contents(dirname(__File__)."/".$term_taxonomy_id,var_export($termArr,true));
				$this->updatetagstat($termArr['term_id'], $termArr['newcount']-$termArr['oldcount']);
			}
		}
		$this->tagcount_update_log=array();
	}

	function updatetagstat($term_id=0, $countchange=0, $timestamp=null)
	{
		$term_id=intval($term_id);
		$countchange=intval($countchange);
		
		global $wpdb;
		if(!($term_id>0) || $countchange==0 ) return;

		if(!$timestamp)$timestamp=time();
		if(!is_int($timestamp))$timestamp=strtotime($timestamp);

		$today = date("Ymd", $timestamp);
		$tomorrow =date("Ymd", mktime(0, 0, 0, date("m",$timestamp)  , date("d",$timestamp)+1, date("Y",$timestamp)));

		$sql="select wpt_id, count from $this->wptTable where term_id='$term_id' and inc_date>='$today' and inc_date<'$tomorrow'";
		$rec=$wpdb->get_row($sql);
		
		$sql="";
		if($rec)
		{
			$recid=$rec->wpt_id;		
			if($countchange < 0)
			{
				if($countchange < -$rec->count)
				{
					$countchange = -$rec->count;
				}
			}
			$sql="update $this->wptTable set count=count + ($countchange) where wpt_id ='$recid'";
		}
		else if($countchange > 0)
		{
			$sql="insert into $this->wptTable (term_id,count,inc_date) values ('$term_id','$countchange','$today')";
		}
		if($sql)$wpdb->query($sql);
	}

	function deletetagstat($term=0, $tt_id=0, $taxonomy=0)
	{
		global $wpdb;
		if(!($term > 0))return;
		$sql="delete from $this->wptTable where term_id='$term'";
		$wpdb->query($sql);
	}

	function install()
	{
		global $wpdb;
		$wptTable=$this->wptTable;

		if($wpdb->get_var("show tables like '$wptTable'") != $wptTable) {

			$sql="CREATE TABLE {$wptTable}
				(
					wpt_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					term_id BIGINT(20) UNSIGNED NOT NULL,
					count BIGINT(20) UNSIGNED default '0',
					inc_date DATETIME NOT NULL default '0000-00-00 00:00:00',
					PRIMARY KEY  (wpt_id),
					INDEX inc_date (inc_date),
					INDEX term_id (term_id)
				)";

			$wpdb->query($sql);			
			$this->preRtdata(10);
			//file_put_contents(dirname(__File__)."/active",$wpdb->query($sql));
		}
	}

	function preRtdata($num)
	{	
		if($num<=0)return;
		set_time_limit(0);
		global $wpdb;
		$termArr=$this->getTagsInRecentPosts($num);
		$lastTerm=$termArr[sizeof($termArr)-1];
		$sql="Delete from $this->wptTable where inc_date < '{$lastTerm['date']}'";
		$wpdb->query($sql);
		foreach ($termArr as $term)
		{
			$this->updatetagstat($term['term_id'], 1, $term['date']);
		}
	}
	
	function getRecentTags($limit, $days)
	{		
		global $wpdb;
		$where="";
		if( is_int($days) && $days > 0)
		{
			$mday =date("Ymd", mktime(0, 0, 0, date("m")  , date("d")-$days, date("Y")));
			$where=" WHERE inc_date >= '$mday' ";
		}
		else if( is_object($days) && $days->post_date)
		{
			$days=strtotime($days->post_date);
			$mday=date("Ymd", mktime(0, 0, 0, date("m",$days)  , date("d",$days), date("Y",$days)));
			$where=" WHERE inc_date >= '$mday' ";
		}
		$sql= "SELECT sum(count) as sum , t.term_id, name FROM $this->wptTable As s INNER JOIN $wpdb->terms As t on s.term_id=t.term_id $where GROUP BY term_id ORDER BY sum DESC LIMIT 0, $limit";
		$terms = $wpdb->get_results($sql);
		return $terms;
	}

	function getRecentTagNameLinks($limit, $days)
	{
		$res=array();
		$terms=$this->getRecentTags($limit, $days);
		foreach ($terms as $term)
		{
			$res[] = array(
			"id"=>$term->term_id,
			"name"=>$term->name,
			"link"=>clean_url(get_tag_link($term->term_id)),
			"sum"=>$term->sum
			);
		}
		return $res;
	}

	function cmplp($a, $b)
	{
		return strcmp($a["name"], $b["name"]);
	}

	function getRecentTagCloud($limit, $days, $levelcount)
	{
		$lpterms=$this->getRecentTagNameLinks($limit, $days);

		$lpsize=sizeof($lpterms);
		$lpStep=floor($lpsize/$levelcount);
		$lpfp=0;
		$lplevel=$levelcount;

		$minsum=intval($lpterms[$lpsize-1]['sum'])-1;
		$maxsum=intval($lpterms[0]['sum']);

		foreach ($lpterms as $key => $lpterm)
		{
			$lpterms[$key]['slevel']=ceil(((floatval($lpterm['sum']))-$minsum)/($maxsum-$minsum)*$levelcount);
			$lpterms[$key]['level']=$lplevel;
			$lpfp++;
			if($lpfp == $lpStep)
			{
				$lplevel--;
				$lpfp=0;
			}
		}

		usort($lpterms, array(&$this, 'cmplp' ));
		return $lpterms;
	}

	function cleartagstat($days)
	{
		global $wpdb;
		$mday =date("Ymd", mktime(0, 0, 0, date("m")  , date("d")-$days, date("Y")));
		$sql = "delete FROM $this->wptTable WHERE inc_date < '$mday' ";
		return $wpdb->query($sql);
	}
	
	function getRecentPostDate($num)
	{
		global $wpdb, $tableposts;	
	    $q = "SELECT post_date FROM $tableposts WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_date DESC limit $num, 1";
	    $res= $wpdb->get_results($q);
	    return $res[0];
	}
	
	function getTagsInRecentPosts($num)
	{
		global $wpdb, $tableposts;	
	    $q = "SELECT ID, post_date FROM $tableposts WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_date DESC limit $num";
	    $res= $wpdb->get_results($q);
	    $termIdArr=array();
	    foreach ($res as $post)
	    {   	
			$tags = get_the_tags($post->ID);
        	if ($tags && is_array($tags)) 
        	{
            	foreach ($tags as $tag) 
            	{
            		$termIdArr[]= array(
	            		"term_id"=>$tag->term_id,
	            		"date"=>$post->post_date
            		);				
            	}
        	}
	    }	    
	    return $termIdArr;
	}
}
endif;

$recentTags= & new RecentTags();
register_activation_hook(__File__, array($recentTags,"install"));
?>