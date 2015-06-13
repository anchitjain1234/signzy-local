<?php $this->assign('title', 'Upload Documents'); ?>

<div class="container">
  <div class="row">
    <div class="col-md-6" style="height:500px;overflow-y:scroll;" id="upload_preview">
        <!-- <img src="img/sample_nda.png" /> -->
        <?php
          if(isset($document))
          {

            $link=$this->Upload->uploadUrl($document, 'Document.avatar' , array('urlize' =>'true'));
            echo "<embed src='".$link."' width = '540' height = '490'></embed>";
          }
          else
          {
            echo "<div class=\"container\" >
                    <div class=\"row align-center\">
                      Your uploaded document will appear here.
                    </div>
                  </div>";
          }
        ?>

    </div>
    <div class="col-md-6">
          <!-- <img src="img/sample_nda.png" /> -->
      <form action="/cakephp/documents/upload2" id="DocumentUpload2Form" enctype="multipart/form-data" method="post" accept-charset="utf-8">
        <div style="display:none;"><input type="hidden" name="_method" value="POST">
        </div>
        <div class="input text form-group has-feedback">
          <label for="DocumentName" class="control-label">Document Name:</label>
          <input class="form-control" name="data[Document][name]" maxlength="2000" type="text" id="DocumentName" placeholder="Name of Document">
        </div>
        <div class="input text form-group has-feedback">
          <label for="DocumentAvatar" class="control-label">Document:</label>
          <input class="form-control" type="file" name="data[Document][avatar]" id="DocumentAvatar" required="required">
        </div>
        <div class="submit form-group has-feedback">
          <input type="submit" value="Upload" class="btn btn-primary">
        </div>
      </form>
    </div>
    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#myModal">Add Signatory</button>

  <!-- Modal -->
 <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="myModalLabel">Add a signatory</h4>
  </div>
  <div class="modal-body">
    <?php echo $this->Form->create('Collabarator' ,array('class'=>'form-horizontal')); ?>

            <div class="form-group">
              <?php
              echo $this->Form->label('name', 'Full Name:', array('class'=>'control-label')); ?>
              <div class="col-sm-8">
                <?php echo $this->Form->input("name",array('class' => 'form-control','placeholder' => 'Name, as per your legal documents' , 'id' => 'name_search','label' => false , 'autocomplete'=>"off" )); ?>
              </div>
            </div>
            <!--
            <div class="form-group">
              <label for="inputEmail3" class="col-sm-4 control-label">Authorized Company</label>

              <div class="col-sm-4">
                <input type="radio" name="company"> Company 1 <br/>
                <input type="radio" name="company"> Company 2 <br/>
                <input type="radio" name="company"> Company 3 <br/>

                <br/>
                <button type="button" class="btn btn-default" id="add_company"><span class="glyphicon glyphicon-plus"></span>Add</button>
              </div>
              <div class="col-sm-4"></div>
            </div>

            <div class="form-group">
              <div class="col-sm-offset-4 col-sm-8">
                <div class="checkbox">
                  <label>
                    <input type="checkbox"> Biometric required?
                  </label>
                </div>
              </div>
            </div>
          -->
          </form>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    <button type="button" class="btn btn-primary">Add</button>
  </div>
</div>
</div>
</div>
  </div>
</div>

<?php echo $this->Html->script('jquery-1.11.1.min.js');
echo $this->Html->script('bootstrap.min.js');
echo $this->Html->script('https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js');
echo $this->Html->script('tagmanager.js');
echo $this->Html->script('upload2.js');?>
