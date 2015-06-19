<?php $this->assign('title', 'Document Info'); ?>
<?php  echo $this->Html->css('show_document', null, array('inline' => false)); ?>


<div class="container">
      <div class="row">
        <div class="col-md-12">
          <h3>Document Trail</h3>
        </div>
      </div>


<div class="row">
  <div class="col-md-10">

        <div class="timeline-centered">

        <article class="timeline-entry">

            <div class="timeline-entry-inner">

                <div class="timeline-icon bg-secondary">
                    <i class="entypo-suitcase"></i>
                </div>

                <div class="timeline-label">
                    <h2>The document is closed</h2>
                    <small class="text-muted"><i class="glyphicon glyphicon-time"></i> On Tuesday 12th January, 2015</small>
                </div>
            </div>

        </article>

        <article class="timeline-entry">

            <div class="timeline-entry-inner">

                <div class="timeline-icon bg-info">
                    <i class="entypo-suitcase"></i>
                </div>

                <div class="timeline-label">
                    <h2><a href="#">Ankit Ratan</a> <span>signed the document with 100% biometric match score</span></h2>
                    <small class="text-muted"><i class="glyphicon glyphicon-time"></i> On Tuesday 12th January, 2015</small>
                </div>
            </div>

        </article>

        <article class="timeline-entry">

            <div class="timeline-entry-inner">

                <div class="timeline-icon bg-info">
                    <i class="entypo-suitcase"></i>
                </div>

                <div class="timeline-label">
                    <h2><a href="#">Akshat Goel</a> <span>signed the document with 100% biometric match score</span></h2>
                    <small class="text-muted"><i class="glyphicon glyphicon-time"></i> On Tuesday 11th January, 2015</small>
                </div>
            </div>

        </article>

        <article class="timeline-entry">

            <div class="timeline-entry-inner">

                <div class="timeline-icon bg-info">
                    <i class="entypo-suitcase"></i>
                </div>

                <div class="timeline-label">
                    <h2><a href="#">Akshat Goel</a> <span>disputed the document</span></h2>
                    <small class="text-muted"><i class="glyphicon glyphicon-time"></i> On Tuesday 10th January, 2015</small>
                </div>
            </div>

        </article>

        <article class="timeline-entry">

            <div class="timeline-entry-inner">

                <div class="timeline-icon bg-info">
                    <i class="entypo-suitcase"></i>
                </div>

                <div class="timeline-label">
                    <h2><a href="#">Ankit Ratan</a> <span>signed the document with 85% biometric match score</span></h2>
                    <small class="text-muted"><i class="glyphicon glyphicon-time"></i> On Tuesday 8th January, 2015</small>
                </div>
            </div>

        </article>

        <article class="timeline-entry">

            <div class="timeline-entry-inner">
                <div class="timeline-icon bg-success">
                    <i class="entypo-feather"></i>
                </div>

                <div class="timeline-label">
                    <h2><a href="#"><?php echo $ownerdata['User']['name'] ?></a> <span>created the document</span></h2>
                    <small class="text-muted"><i class="glyphicon glyphicon-time"></i> On <?php echo date('l d F, Y ', $docudata['Document']['created']->sec); ?></small>
                </div>
            </div>

        </article>


        <article class="timeline-entry begin">

            <div class="timeline-entry-inner">

                <div class="timeline-icon" style="-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg);">
                    <i class="entypo-flight"></i> +
                </div>

            </div>

        </article>

    </div>
    </div>
  </div>


  <?php echo $this->Html->script('jquery-1.11.1.min.js');
  echo $this->Html->script('bootstrap.min.js');?>
