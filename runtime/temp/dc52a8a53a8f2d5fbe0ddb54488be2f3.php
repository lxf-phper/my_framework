<html>
<head>
<title>测试</title>
</head>
<body>
<div>
<?php echo $test; ?>
<?php echo $aaa; ?>
    <br/><br/>
    <?php foreach ($list as $key => $vo) { ?>
    <?php echo $key; ?>.<?php echo $vo; ?><br/>
    <?php } ?>
<br/>
    <?php foreach ($list as $k => $vo) { ?>
    <?php echo $k; ?>{$vo.id}:{$vo.name}<br/>
    <?php } ?>
</div>
</body>
</html>