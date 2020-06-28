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
    <?php foreach ($lists as $k => $vo) { ?>
    <?php echo $k; ?><?php echo $vo['id']; ?>:<?php echo $vo['name']; ?><br/>
    <?php } ?>
    <br/>
    <?php $test = 123;$test2 = 100; if ($test>$test2){ ?>
    <button>按钮</button>
    <?php } ?>
</div>
</body>
</html>