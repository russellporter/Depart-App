<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $title_for_layout?></title>
	<link rel="shortcut icon" href="/favicon.png" type="image/png" />
	<link rel="apple-touch-icon" href="/img/apple-touch-icon.png" />
	<meta charset="utf-8">
	<meta content='True' name='HandheldFriendly' />
	<meta content='width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;' name='viewport' />
	<meta name="viewport" content="width=device-width" />
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="/js/jquery-1.6.2.min.js"></script>
	<script type="text/javascript" src="/js/jquery.blockUI.js"></script>
	<script type="text/javascript" src="/js/jquery.pngFix.pack.js"></script>
	<link rel="stylesheet" type="text/css" href="/css/transit.css" />
	<?php echo $scripts_for_layout ?>
	<script type="text/javascript">
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-2618724-5']);
	  _gaq.push(['_trackPageview']);
	
	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();
	</script>
</head>
<body>

<?php echo $content_for_layout ?>


<div id="footer"></div>

</body>
</html>