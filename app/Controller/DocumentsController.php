<?php
App::uses('CakeEmail', 'Network/Email');
App::uses('Validation', 'Utility');
class DocumentsController extends AppController
{
  public $uses = array('Document');
  var $helpers = array('Form', 'UploadPack.Upload');
  public $layout = 'insidelayout';



  public function beforeFilter()
	{
		/*
		To give the name ofthe user to the layout befire the view is loaded.
		*/

    $this->loadModel('User');
    $uid = CakeSession::read("Auth.User.id");
    $params = array(
      'fields' => array('name', 'verified'),
      'conditions' => array('id' => $uid),
    );
    $userdata=$this->User->find('first',$params);
    $this->set('name',$userdata['User']['name']);
	}



  public function index()
  {
    /*
    To display all the previous uploaded documents of the user.
    */

    $uid = CakeSession::read("Auth.User.id");
    $parameters = array(
      'fields' => array('id','avatar_file_name','name'),
      'conditions' => array('ownerid'=> $uid)
    );
    $this->set('user_documents_data',$this->Document->find('all',$parameters));

  }



  public function upload2()
  {
    if($this->request->is('post'))
    {
      //$data = $this->request->input('json_decode',true);
      //$emails  = json_decode($this->request->data['emails_hidden']);
      //$this->log($emails);
      //print_r($emails);
      //print_r($this->request->data);

      if (!empty($this->request->data))
      {

  			$this->Document->create($this->data);
        $this->request->data['Document']['ownerid']= CakeSession::read("Auth.User.id");

        /*
        If Docuemnt is valid i.e. its pdf or doc and has size<100 MB
        */
        if($this->Document->validates())
        {
          if ($this->Document->save($this->request->data))
          {
            /*
            If Document is saved successfully than we can save each collabarator in col model
            */
            $this->loadModel('Col');
            $emails  = json_decode($this->request->data['emails_hidden']);
            $document_name  = $this->request->data['Document']['name'];

            /*
            Get document id where ownerid = current user id and name = current uploaded document name
            */
            $params = array(
              'fields' => array('id'),
              'conditions' => array('name' => $document_name,'ownerid' => CakeSession::read("Auth.User.id")),
            );
            $docudata=$this->Document->find('first',$params);

            /*
            For each entered email address we will first check whether its a valid email address or not.
            If we find even if one email is invalid it means that user is manipulating our javascript so
            we will not save the collabarators data for this request.
            */
            foreach($emails as $email):
              if(!Validation::email($email))
              {
                throw new NotFoundException(__('Error while saving data.'));
              }
            endforeach;

            foreach($emails as $email):
              $this->Col->create();
              $this->Col->set('did',$docudata['Document']['id']);

              /*
              Change status here to default status
              */
              $this->Col->set('status',0);
              $params = array(
                'fields' => array('id','name'),
                'conditions' => array('username' => $email),
              );
              $userdata=$this->User->find('first',$params);
              $token=str_shuffle(hash("sha512",(hash("sha256",$email
                                      .$userdata['User']['name'])).strval(time()).md5(rand())));
              $this->Col->set('token',$token);
              $this->Col->set('uid',$userdata['User']['id']);
              $this->Col->save();

              /*
              Send email to the saved collabarator
              */
              $sign_document_email=new CakeEmail('mandrill_signup');
  						$sign_document_email->to($email);
  						$sign_document_email->subject('Document Signing Request');
              $sign_document_email->template('sign_document_request','notification_email_layout')
                                  ->viewVars(array('document_signing_link' =>
   																								 Router::url( array('controller' => 'documents',
   																																		'action' => 'sign' ,
   																																		"?" => [
   																																			"username" => $email
   																																			,"token" => $token
                                                                        ,"document" => $docudata['Document']['id']])
   																																			, true ),
   																									'name_of_user' => $userdata['User']['name']));
              $sign_document_email->send();

            endforeach;
            $this->Session->setFlash(__('Document uploaded and emails sent to all the signatories successfully.'),'flash_success');
            $this->set('document', $this->Document->findById($this->Document->id));
    			}
          else
          {
            $this->Session->setFlash(__('Your document couldn\'t be uploaded.'),'flash_error');
          }
        }
        else
        {
          $this->Session->setFlash(__('Your document couldn\'t be uploaded.'),'flash_error');
        }
  		}
    }
  }

}

?>
