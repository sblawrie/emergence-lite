<?php
function Dwoo_Plugin_paginate(Dwoo $dwoo, $baseurl, $currpage, $numperpage, $total, array $args = null)
{
	$default = array(
		'alwaysShowFirst' => TRUE,
		'alwaysShowLast' => TRUE,
		'showPrevious' => TRUE,
		'showNext' => TRUE,
		'padding' => 1
	);
	
	$args = !empty($args) && is_array($args) ? array_merge($default, $args) : $default;
	
	$totalpages = ceil($total / $numperpage);
	
	$html = '';
	if($totalpages > 1)
	{
		$querystring = $_SERVER['QUERY_STRING'];	
	
		// begin list
		$html = '<div class="pagination pagination-centered"><ul>';
		
		// add previous link if it's not the first page
		if(!empty($args['showPrevious']) && $currpage > 1)
		{
			parse_str($querystring, $qsvars);
			$qsvars['page'] = !empty($qsvars['page']) ? $qsvars['page'] - 1 : $currpage - 1;
			$html .= '<li class="first"><a class="prev" href="'.$baseurl.'?'.http_build_query($qsvars).'">Previous</a></li>';
		}
		
		// figure out where to start the loop
		$diff = $totalpages - ($currpage + $args['padding']);
		if($diff < 0)
		{
			$loopStart = $currpage - $args['padding'] + $diff;
			$loopStart = $loopStart <= 0 ? 1 : $loopStart;
		}
		else
		{
			$padded = $currpage - $args['padding'];
			$loopStart = $padded > 0 ? $padded : 1;
		}
			
		// show the first page with an elipsis if alwaysShowFirst is true and the current page + the padding is greater than 1
		if(!empty($args['alwaysShowFirst']) && $loopStart > 1)
		{
			$qsvars['page'] = 1;
			$html .= '<li class="pagenum pagenum-first"><a href="'.$baseurl.'?'.http_build_query($qsvars).'">1</a></li>';
			
			if($loopStart - 1 != 1)
				$html .= '<li class="disabled ellipsis"><a href="#" onclick="return false;">...</a></li>';
		}
		
		// figure out where to end the loop
		$diff = $currpage - 1 - $args['padding'];
		if($diff < 0)
		{
			$loopEnd = $currpage + $args['padding'] - $diff;
			$loopEnd = $loopEnd > $totalpages ? $totalpages : $loopEnd;
		}
		else
		{
			$sum = $currpage + $args['padding'];
			$loopEnd = $sum > $totalpages ? $totalpages : $sum;
		}
		
		for($i = $loopStart; $i <= $loopEnd; ++$i)
		{
			$activeClass = $currpage == $i ? ' active' : '';
			$leftPadClass = $i == $loopStart ? ' pagination-leftpad' : '';
			parse_str($querystring, $qsvars);
			$qsvars['page'] = $i;
				
			$html .= '<li class="pagenum'.$activeClass.$leftPadClass.'"><a href="'.$baseurl.'?'.http_build_query($qsvars).'">'.$i.'</a></li>';
		}
		
		if(!empty($args['alwaysShowLast']) && $loopEnd < $totalpages)
		{
			if($loopEnd + 1 != $totalpages)
				$html .= '<li class="disabled ellipsis"><a href="#" onclick="return false;">...</a></li>';
			
			$qsvars['page'] = $totalpages;	
			$html .= '<li class="pagenum pagenum-last"><a href="'.$baseurl.'?'.http_build_query($qsvars).'">'.$totalpages.'</a></li>';
		}
		
		if(!empty($args['showNext']) && $currpage < $totalpages)
		{
			parse_str($querystring, $qsvars);
			$qsvars['page'] = !empty($qsvars['page']) ? $qsvars['page'] + 1 : $currpage + 1;
			$html .= '<li class="last"><a class="next" href="'.$baseurl.'?'.http_build_query($qsvars).'">Next</a></li>';
		}
		
		$html .= '</ul></div>';
	}

	return $html;
}
