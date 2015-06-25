<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $this->fetch('title'); ?></title>

    <!-- Bootstrap -->
    <?php echo $this->Html->css('bootstrap.min' ); ?>
    <?php echo $this->fetch('css'); ?>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style type="text/css">
      body {
        padding-top: 70px;
      }

      .bigguy {
        font-size: 5em;
      }

      a {
        color: black;
      }
    </style>
  </head>
  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <?php echo $this->Html->link('VerySure™',array('controller'=>'users','action'=>'index'),array('class' => 'navbar-brand'));?>
        </div>
        <ul class="nav navbar-nav">
          <li><?php echo $this->Html->link('Documents',array('controller'=>'documents','action'=>'index'))?></li>
          <li><?php echo $this->Html->link('New Document',array('controller'=>'documents','action'=>'upload'))?></li>
        </ul>

        <div id="navbar" class="navbar-collapse collapse navbar-right">
          <ul class="nav navbar-nav">
            <li><?php echo $this->Html->link("Hi, $name",array('controller'=>'profile','action'=>'index'))?></li>
            <!-- <li role="presentation"><a href="#">Notifications <span class="badge">3</span></a></li> -->
            <li><?php echo $this->Html->link('Logout',array('controller'=>'users','action'=>'signout'))?></li>
          </ul>
        </div><!--/.navbar-collapse -->
      </div>
    </nav>
    <?php echo $this->Session->flash(); ?>
    <?php echo $this->fetch('content'); ?>
    <?php echo $this->fetch('script'); ?>
    <footer>
      <p>&copy; VerySure™ 2014</p>
    </footer>
  </body>
  <?php echo $this->element('sql_dump'); ?>
</html>
