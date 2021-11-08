<?php

/**
 * A full-featured feature item view that can be used as a default
 *
 * Here is sample markup for a page with two features attached that have the following characteristics:
 *
 * - The features each have bg_color set to #ff0000
 * - The first feature is an image, the second is a video.
 * - Looping is enabled
 *
 * 	<div class="featuresModule autoplay-5 looping-on noscript">
 *	<ul class="features">
 *	<li class="feature active" style="background-color:#ff0000;">
 *		<div class="featureContent featureTypeImage">
 *		<a href="http://destination_url" class="dest"><img alt="image caption" src="/reason/sized_images/730793/4d378bcfa56dfd78c5af82fe2bbe73f7.jpg" /></a>
 *		<h3 class="featureTitle"><a href="http://destination_url" class="dest">Feature 1 Title</a></h3>
 *		<div class="featureText"><a href="http://destination_url" class="dest">Feature 1 Text</a></div>
 *		<div class="featureNav"><a href="?feature=12345" title="Feature 1 Title" class="current num1"></a><a href="?feature=23456" title="Feature 2 Title" class="nonCurrent num2"></a></div>
 *		</div>
 *	</li>
 *	<li class="feature inactive" style="background-color:#ff0000;">
 *		<div class="featureContent featureTypeVideo">
 *		<div class="featureImage">
 *		<a href="#featureVideo-11111"><img alt="image_caption" src="/reason/sized_images/730793/4d378bcfa56dfd78c5af82fe2bbe73f7.jpg" /></a>
 *		</div>
 *
 *		<div class="featureVideo" id="featureVideo-11111">
 * 		<object type="application/x-shockwave-flash" data="media_player.swf?file=http://rtsp.carleton.edu/media_file.mp4&amp;autostart=false&amp;image=/reason/images/730690.jpg" width="840" height="650" id="flashVideoWidget728524">
 *		<param name="movie" value="/global_stock/flv_media_player/player.swf?file=http://rtsp.carleton.edu/media_file.mp4&amp;autostart=false&amp;image=/reason/images/730690.jpg">
 *		<param name="allowfullscreen" value="true">
 *		</object>
 *		<h3 class="featureTitle"><a href="http://destination_url" class="dest">Feature 2 Title</a></h3>
 *		<div class="featureText"><a href="http://destination_url" class="dest">Feature 2 Text</a></div>
 *		</div>
 *
 *		<a href="http://destination_url"><img alt="image caption" src="/reason/sized_images/730793/4d378bcfa56dfd78c5af82fe2bbe73f7.jpg" /></a>
 *		<h3 class="featureTitle"><a href="http://destination_url">Feature 2 Title</a></h3>
 *		<div class="featureText"><a href="http://destination_url">Feature 2 Text</a></div>
 *		<div class="featureNav"><a href="?feature=12345" title="Feature 1 Title" class="nonCurrent num1"></a><a href="?feature=23456" title="Feature 2 Title" class="current num2"></a></div>
 *		</div>
 *	</li>
 *	</ul>
 *	</div>
 *
 * @todo no need for nyromodal if there are no videos
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include the abstract class this view will inherit from
 */
reason_include_once( 'minisite_templates/modules/feature/views/default_view.php' );

/**
 * A full-featured feature item view that can be used as a default
 */
class DefaultFeatureView extends FeatureView
{
	var $has_av = false;
	var $custom_class = '';

	function set($view_data,$view_params,$current_feature_id,&$head_items)
	{
		//call Mother
		parent::set($view_data,$view_params,$current_feature_id,$head_items);

		//Make sure all view data have default values
		//if they haven't been set
		foreach($view_data as $id=>$data)
		{
			if(!isset($data['id'])){ $view_data[$id]['id']=0; }
			if(!isset($data['destination_url'])){ $view_data[$id]['destination_url']=null; }
			if(!isset($data['active'])){  $view_data[$id]['active']="inactive"; }
			if(!isset($data['bg_color'])){ $view_data[$id]['bg_color']="ffffff"; }
			if(!isset($data['title'])){ $view_data[$id]['title']="";}
			if(!isset($data['text'])){ $view_data[$id]['text']="";}
			if(!isset($data['w'])){ $view_data[$id]['w']=$this->default_width;}
			if(!isset($data['h'])){ $view_data[$id]['h']=$this->default_height;}
			if(!isset($data['show_text'])){ $view_data[$id]['show_text']=0;}
			if(!isset($data['current_image_index'])){ $view_data[$id]['current_image_index']=0;}
			if(!isset($data['current_object_type'])){ $view_data[$id]['current_object_type']="img";}
			if(!isset($data['feature_image_url'])){ $view_data[$id]['feature_image_url']=array("none");}
			if(!isset($data['feature_image_alt'])){ $view_data[$id]['feature_image_alt']=array("");}
			if(!isset($data['feature_av_html'])){ $view_data[$id]['feature_av_html']=array("none");}
			if(!isset($data['feature_av_img_url'])){ $view_data[$id]['feature_av_img_url']=array("none");}
			if(!isset($data['feature_av_img_alt'])){ $view_data[$id]['feature_av_img_alt']=array("");}
			if ($view_data[$id]['current_object_type'] == 'av') $this->has_av = true;
		}

		//eliminate array elements that do not belong to a category
		if( !empty($view_params['categories']) )
		{
			// build a unique name for styling multiple features on a page
			foreach ($view_params['categories'] as $category) {
				$category = preg_replace('/\s+/', '', $category);
				$category = strtolower($category);
				$this->custom_class .= ucfirst( $category );
			}
			$this->custom_class = '.'.lcfirst( $this->custom_class );

		} else {
			$this->custom_class = '.noCat';
		}

		$this->_view_data=$view_data;

		$width=$this->_view_params['width'];
		$height=$this->_view_params['height'];
		if($head_items != null)
		{
			$head_items->add_javascript(JQUERY_URL, true);
			if ($this->has_av)
			{
				$head_items->add_javascript($this->absolutify_url_if_needed(REASON_PACKAGE_HTTP_BASE_PATH."nyroModal/js/jquery.nyroModal-1.6.2.min.js"));
			}
			$head_items->add_javascript($this->absolutify_url_if_needed(REASON_HTTP_BASE_PATH . 'js/feature.js'));
			$head_items->add_stylesheet($this->absolutify_url_if_needed(REASON_HTTP_BASE_PATH . 'css/features/feature_responsive.css?v=2'));
			$head_items->add_head_item("style",array("type"=>"text/css"),"
			.featuresModule$this->custom_class { max-width: ".$width."px; }
			$this->custom_class .features { padding-bottom:" . round($height/$width*100, 5) . "%; }
			",false);
		}

	}// end set function


	/**
	* @return the features nav strings
	*/
	function build_feature_nav_strs()
	{
		$view_data=$this->get_view_data();
		if(count($view_data)>1)
		{
			foreach($view_data as $d)
			{
				$str=$this->build_feature_nav_str($d['id']);
				$this->_view_data[$d['id']]['feature_nav_str']=$str;
			}
		}
		else
		{
			foreach($view_data as $d)
			{
				$this->_view_data[$d['id']]['feature_nav_str']="<div class=\"featureNav\"></div>";
			}

		}

	}

	/**
	* build the html string for navigating features
	*/
	function build_feature_nav_str($curr_id)
	{
		$data=$this->get_view_data();
		$feature_num=1;
		$str ="<div class=\"featureNav\">";
		if($this->_view_params['autoplay_timer'] != 0)
		{
			$str .= '<span class="play-pause">';
			$end = end($data);
			reset($data);
			if ( ($end['id'] == $curr_id) && ($this->_view_params['looping'] == 'off') )
			{
				$str .=$this->build_start_over_str();
			}
			else
			{
				$str .=$this->build_play_nav_str();
				$str .=$this->build_pause_nav_str();
			}
			$str .= '</span>';
		}
		$str .= '<span class="navBlock">';
		$str .= $this->build_arrow_nav_str("prev",$curr_id);
		if(!$this->_view_params['condensed_nav'])
		{
			foreach($data as $d)
			{
				$anchor_class="";
				$id=$d['id'];
				if($id==$curr_id)
				{
					$anchor_class="current";
				}
				else
				{
					$anchor_class="nonCurrent";
				}

				$title=$d['title'];
				$str.="<a  href=\"?feature=".$id."\" title=\"".reason_htmlspecialchars($title)."\" class=\"".$anchor_class." navItem\">"."<span>".$feature_num."</span></a>";
				$feature_num++;
			}
		}
		$str.=$this->build_arrow_nav_str("next",$curr_id);
		$str .= '</span>';
		$str.="</div>\n";

		return $str;

	}
	/**
	* build an html string for arrow navigation
	*
	* @param $direction looks for two values "prev" or "next"
	* @return string containing html for next or prev links in the
	*         feature navigation string
	*/
	function build_arrow_nav_str($direction,$curr_id)
	{
		$str="";
		$data=$this->get_view_data();
		reset($data);
		$id=0;
		$title="";
		$ids=array();
		$titles=array();
		$arrow="&lt;";
		$class="button prev";
		$looping=$this->_view_params['looping'];

		//build the arrays of ids and titles
		foreach($data as $d)
		{
			$ids[]=$d['id'];
			$titles[]=$d['title'];
		}

		//find the index of the curr_id in the ids array
		$curr_index=0;
		$n=count($ids);
		for($i=0;$i<$n;$i++)
		{
			if($ids[$i]==$curr_id)
			{
				$curr_index=$i;
				break;
			}
		}
		//determine which feature should be used as
		// next or previous on the feature navigation
		if($direction=="prev")
		{
			if($curr_index==0 && $looping=="on")
			{
				$id=$ids[$n-1];
				$title=$titles[$n-1];
			}
			elseif($curr_index==0 && $looping=="off")
			{
				$id=$ids[0];
				$title=$titles[0];
			}
			else
			{
				$id=$ids[$curr_index-1];
				$title=$titles[$curr_index-1];
			}
		}
		else if($direction=="next")
		{
			$arrow="&gt;";
			$class="button next";
			if( $curr_index== ($n-1) && $looping=="on" )
			{
				$id=$ids[0];
				$title=$titles[0];
			}
			elseif($curr_index==($n-1) && $looping=="off")
			{
				$id=$ids[$n-1];
				$title=$titles[$n-1];
			}
			else
			{
				$id=$ids[$curr_index+1];
				$title=$titles[$curr_index+1];
			}
		}
		$str="<a href=\"?feature=".$id."\" title=\"".reason_htmlspecialchars($title)."\" class=\"".$class." \">"."<span>".$arrow."</span></a>";
		return $str;


	}// end build_arrow_nav_str function



	/**
	* @return string containing a play link for when feature is in slide show mode
	*/
	function build_play_nav_str()
	{
		$str="<a style=\"display:none\" href=\"#\" title=\"Play Slide Show\" class=\"button play \"> Play </a>";
		return $str;
	}

	/**
	* @return string containing a pause link for when feature is in slide show mode
	*/
	function build_pause_nav_str()
	{
		$str="<a style=\"display:none\" href=\"#\" title=\"Pause Slide Show\" class=\"button pause \"> Pause </a>";
		return $str;
	}

	/**
	* @return string containing a start over link for when features is in slide show mode and looping is off
	*/
	function build_start_over_str()
	{
		$str="<a style=\"display:none\" href=\"#\" title=\"Start Over\" class=\"button startOver \"> Start Over </a>";
		return $str;
	}


	/**
	 * Create the markup
	 * @return string containing html markup for features
	 */
	function get_html()
	{
		$str="";
		$this->build_feature_nav_strs();

		$view_data = $this->get_view_data();
		$timer=$this->_view_params['autoplay_timer'];
		if ( $this->_view_params['initial_offset'])
			$timer = $this->_view_params['initial_offset'] + $timer;
		$looping=$this->_view_params['looping'];

		$count = 'count'.count($view_data);
		$class = ltrim($this->custom_class, '.');
		$str = "<div class=\"featuresModule $class autoplay-$timer looping-$looping noscript $count\">\n";

		$str.= "<ul class=\"features\">\n";
		$str.= "";
		foreach($view_data as $data)
		{
			$str.=$this->get_html_list_item($data);
		}
		$str.="</ul>\n";
//		$str.=$this->build_feature_nav_str($curr_id);
		$str.="</div>\n";
		return $str;

	}

	/**
	* @return a string of html markup of a list item
	* basically the markup for one feature
	*/
	function get_html_list_item($view_data)
	{
		$has_anchor=true;
		if($view_data['destination_url']==null){$has_anchor=false;}

		$height=$this->_view_params['height'];

		$anchor_start='<span class="noLink">';
		$image_anchor_start='<span class="noLink">';
		$anchor_end='</span>';
		$image_anchor_end='</span>';

		if($has_anchor)
		{
			$image_anchor_start = "<a href=\"".$this->absolutify_url_if_needed($view_data['destination_url'])."\" class=\"dest\">"; //style=\"height:".$height."px;\">";
			$image_anchor_end="</a>";
			$anchor_start="<a href=\"".$this->absolutify_url_if_needed($view_data['destination_url'])."\" class=\"dest\">";
			$anchor_end="</a>";
		}

		$media_str="";
		$type_str="";
		if($view_data['current_object_type']=='img')
		{
			$img_url=$view_data['feature_image_url'][$view_data['current_image_index']];
			$img_alt=$view_data['feature_image_alt'][$view_data['current_image_index']];
			if($img_url!="none")
			{
					$media_str ="<div class=\"featureImage\" >\n";
					$media_str.=$image_anchor_start."<img alt=\"".reason_htmlspecialchars($img_alt)."\" name=\"big_pic\" src=\"".$img_url."\" />".$image_anchor_end."\n";
					$media_str.="</div>";
			}
			$type_str = ' featureTypeImage';
		}
		elseif($view_data['current_object_type']=='av')
		{
			$av_html=$view_data['feature_av_html'][$view_data['current_image_index']];
			$img_url=$view_data['feature_av_img_url'][$view_data['current_image_index']];
			$img_alt=$view_data['feature_av_img_alt'][$view_data['current_image_index']];
			$img_id="featureVideo-".$view_data['id'];
			$image_anchor_start="<a href=\"#".$img_id."\" style=\"height:".$height."px;\" class=\"anchor\">\n";
			$image_anchor_end="</a>";
			$text_anchor_start = "<a href=\"#".$img_id."\" class=\"anchor\">\n";
			$text_anchor_end = '</a>';

			if($img_url!="none")
			{
				$link = ($has_anchor) ? ' (' .$anchor_start . 'more' . $anchor_end . ')' : '';
				$media_str ="<div class=\"featureImage\" >\n";
				$media_str.=$image_anchor_start."<img alt=\"".reason_htmlspecialchars($img_alt)."\"  name=\"big_pic\" src=\"".$img_url."\" />".$image_anchor_end."\n";
				$media_str.="</div>";
				$media_str.="<div class=\"featureVideo nofitvids\" id=\"".$img_id."\" style=\"\">";
				$media_str .= $av_html;
				$media_str .= '<h3 class="featureTitle">'.$view_data['title'].'</h3>'."\n";
				$media_str .= '<div class="featureText">'.$view_data['text'].$link.'</div>'."\n";
				$media_str.="</div>";
			}
			$type_str = ' featureTypeVideo';
		}
		$title_str="";
		$text_str="";
		if($view_data['show_text'])
		{
			if ($view_data['current_object_type'] == 'av') // our links go to the movie not the dest url
			{
				$title_str="<h3 class=\"featureTitle\">".$text_anchor_start.$view_data['title'].$text_anchor_end."</h3>\n";
				if(!empty($view_data['text']))
				{
					$text_str="<div class=\"featureText\">".$text_anchor_start.$view_data['text'].$text_anchor_end."</div>\n";
				}
			}
			else
			{
				$title_str="<h3 class=\"featureTitle\">".$anchor_start.$view_data['title'].$anchor_end."</h3>\n";
				if(!empty($view_data['text']))
				{
					$text_str="<div class=\"featureText\">".$anchor_start.$view_data['text'].$anchor_end."</div>\n";
				}
			}
		}

		$str ="<li id=\"feature-".$view_data['id']."\" class=\"feature ".$view_data['active']." sizable cropStyle-".reason_htmlspecialchars($view_data['crop_style'])."\"
		 		style=\"background-color:#".$view_data['bg_color'].";\" >\n";
		$str.='<div class="featureContent' .$type_str.'">'."\n";
			$str.=$media_str;
			$str.="<div class=\"featureInfo\">";
				$str.=$title_str;
				$str.=$text_str;
				$str.=$view_data['feature_nav_str'];
				$str.="</div>";//end featureInfo div

		$str.="</div>\n";//end featureContent div
		$str.="</li>\n";

		return $str;
	}


}
