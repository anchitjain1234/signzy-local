<?php

App::uses('CakeEmail', 'Network/Email');
App::uses('Validation', 'Utility');

class DocumentsController extends AppController {

    public $uses = array('Document');
    var $helpers = array('Form', 'UploadPack.Upload');
    public $layout = 'insidelayout';

    public function beforeFilter() {
        /*
          To give the name ofthe user to the layout befire the view is loaded.
         */

        $this->loadModel('User');
        $uid = CakeSession::read("Auth.User.id");
        $params = array(
            'fields' => array('name', 'username'),
            'conditions' => array('id' => $uid),
        );
        $userdata = $this->User->find('first', $params);
        $this->set('name', $userdata['User']['name']);
        $this->set('useremail', $userdata['User']['username']);
    }

    public function index() {
        /*
          To display all the previous uploaded documents of the user as well as the documents in which the
          user id requested to sign it.
         */

        $uid = CakeSession::read("Auth.User.id");
        $parameters = array(
            'conditions' => array('ownerid' => $uid)
        );
        $user_documents_data = $this->Document->find('all', $parameters);

        /*
          Now finding documents in which user is a collabarator
         */
        $this->loadModel('Col');
        $parameters = array(
            'fields' => array('did'),
            'conditions' => array('uid' => $uid)
        );
        $coldata = $this->Col->find('all', $parameters);
        if ($coldata) {
            foreach ($coldata as $col):
                $parameters = array(
                    'conditions' => array('id' => $col['Col']['did'])
                );
                array_push($user_documents_data, $this->Document->find('first', $parameters));
            endforeach;
        }
        $this->set('user_documents_data', $user_documents_data);
    }

    public function upload() {

        if ($this->request->is('post')) {
            $this->autorender = false;
            $this->layout = false;
            $emails = json_decode($this->request->data['emails']);
            $current_name = $this->request->data['doc_name'];
            /*
             * If user does not specifies the document name ,we would give name of the document as its original name.
             */
            if (isset($this->request->data['name']) && $this->request->data['name'] != "") {
                $change_to_name = $this->request->data['name'];
            } else {
                $change_to_name = $this->request->data['doc_org_name'];
            }
            $original_name = $this->request->data['doc_org_name'];
            $size = $this->request->data['doc_size'];
            $type = $this->request->data['doc_type'];

            /*
             * Saving document data . In document table "originalname" refers to the name 
             * by ehich file is stored in the storage area
             * and "name" refers to the name given by user.
             */
            $this->Document->create();
            $this->Document->set('name', $change_to_name);
            $this->Document->set('size', $size);
            $this->Document->set('type', $type);
            $this->Document->set('originalname', $current_name);
            $this->Document->set('ownerid', CakeSession::read("Auth.User.id"));
            $this->Document->set('status', "0");
            if ($this->Document->save()) {
                /*
                 * Checking if any signatories are there or not.
                 */
                if (count($emails) > 0) {
                    $docid = $this->Document->find('first', array('conditions' => array('originalname' => $current_name)
                        , 'fields' => array('id')));

                    /*
                     * Checking if any of the emails goven in signatory is invalid or not.
                     * If anyone is invalid it does means that user is tampering so we should put him in risky category.
                     */
                    foreach ($emails as $email):
                        if (!Validation::email($email)) {
                            throw new NotFoundException(__('Error while saving data.'));
                        }
                    endforeach;

                    $this->loadModel('Col');
                    $this->loadModel('User');

                    foreach ($emails as $email):
                        /*
                         * Finding user related to the email.
                         */
                        $userdata = $this->User->find('first', array('conditions' => array('username' => $email)));
                        /*
                         * Saving corresponding data in cols table.
                         */
                        $this->Col->create();
                        $this->Col->set('did', $docid['Document']['id']);
                        /*
                          Change status here to default status
                         */
                        $this->Col->set('status', Configure::read('doc_pending'));
                        $token = $this->generate_token($email, $userdata['User']['name']);
                        $this->Col->set('token', $token);
                        $this->Col->set('uid', $userdata['User']['id']);
                        if ($this->Col->save()) {
                            /*
                              Send email to the saved collabarator
                             */
                            $document_signing_link = Router::url(array('controller' => 'documents',
                                        'action' => 'sign',
                                        "?" => [
                                            "userid" => $userdata['User']['id']
                                            , "token" => $token
                                            , "docuid" => $docid['Document']['id']])
                                            , true);
                            $this->sendemail('sign_document_request', 'notification_email_layout', $userdata, $document_signing_link, 'Document Signing Request');
                        }
                        else
                        {
                            echo '{"finaldocstatus":false,"error":2}';
                        }
                    endforeach;
                    echo '{"finaldocstatus":true}';
                    $this->Session->setFlash(__('Document uploaded suceessfully and mails sent to all signatories'), 'flash_success');
                    exit;
                }
                /*
                 * Case when no signatories are present.
                 */
                echo '{"finaldocstatus":false,"error":1}';
                exit;
            }
            /*
             * Case when document data can't be saved into the table.
             */
            echo '{"finaldocstatus":false,"error":2}';
            exit;
        }
    }

    public function upload_ajax() {
        $this->autorender = false;
        $this->layout = false;
        $allowed = array('doc', 'pdf', 'docx');
        if (isset($_FILES['data']['name']['Document']['file']) && $_FILES['data']['error']['Document']['file'] == 0) {

            $extension = pathinfo($_FILES['data']['name']['Document']['file'], PATHINFO_EXTENSION);

            if (!in_array(strtolower($extension), $allowed)) {
                echo '{"documentstatus" : false , "error": -1}';
                exit;
            }

            /*
             * get_temporary_document_name is defined in AppController.php
             */
            $temporary_document_name = $this->get_temporary_document_name();
            $temporary_document_name = $temporary_document_name . "." . $extension;

            /*
             * Saving document so that document doesn't gets
             * lost from the temporary files.
             */
            if (move_uploaded_file($_FILES['data']['tmp_name']['Document']['file'], WWW_ROOT . 'uploads/' . $temporary_document_name)) {

                echo '{"documentstatus" : true '
                . ',"documentname" : "' . $temporary_document_name . '"'
                . ',"documentsize":' . $_FILES['data']['size']['Document']['file'] . ''
                . ',"documentoriginalname":"' . $_FILES['data']['name']['Document']['file'] . '"'
                . ',"documenttype":"' . $_FILES['data']['type']['Document']['file'] . '"}';
                exit;
            }
            echo '{"documentstatus" : false , "error":' . $_FILES['data']['error']['Document']['file'] . '}';
        }
        echo '{"documentstatus" : false }';
    }

    public function upload2() {
        if ($this->request->is('post')) {
            //$data = $this->request->input('json_decode',true);
            //$emails  = json_decode($this->request->data['emails_hidden']);
            //$this->log($emails);
            //print_r($emails);
            //print_r($this->request->data);

            if (!empty($this->request->data)) {

                $this->Document->create($this->data);
                $this->request->data['Document']['ownerid'] = CakeSession::read("Auth.User.id");

                /*
                  If Document is valid i.e. its pdf or doc and has size<100 MB
                 */
                if ($this->Document->validates()) {
                    $this->request->data['Document']['status'] = "0";
                    if ($this->Document->save($this->request->data)) {
                        /*
                          If Document is saved successfully than we can save each collabarator in col model
                         */
                        $this->loadModel('Col');
                        $emails = json_decode($this->request->data['emails_hidden']);
                        if (count($emails) > 0) {
                            $document_name = $this->request->data['Document']['name'];

                            /*
                              Issue here we should make document name unique for each user
                              Get document id where ownerid = current user id and name = current uploaded document name
                             */
                            $params = array(
                                'fields' => array('id'),
                                'conditions' => array('name' => $document_name, 'ownerid' => CakeSession::read("Auth.User.id")),
                            );
                            $docudata = $this->Document->find('first', $params);

                            /*
                              For each entered email address we will first check whether its a valid email address or not.
                              If we find even if one email is invalid it means that user is manipulating our javascript so
                              we will not save the collabarators data for this request.
                             */
                            foreach ($emails as $email):
                                if (!Validation::email($email)) {
                                    throw new NotFoundException(__('Error while saving data.'));
                                }
                            endforeach;

                            foreach ($emails as $email):
                                $this->Col->create();
                                $this->Col->set('did', $docudata['Document']['id']);

                                /*
                                  Change status here to default status
                                 */
                                $this->Col->set('status', "0");
                                $params = array(
                                    'fields' => array('id', 'name'),
                                    'conditions' => array('username' => $email),
                                );
                                $userdata = $this->User->find('first', $params);
                                $token = str_shuffle(hash("sha512", (hash("sha256", $email
                                                        . $userdata['User']['name'])) . strval(time()) . md5(rand())));
                                $this->Col->set('token', $token);
                                $this->Col->set('uid', $userdata['User']['id']);
                                $this->Col->save();

                                /*
                                  Send email to the saved collabarator
                                 */
                                $sign_document_email = new CakeEmail('mandrill_signup');
                                $sign_document_email->to($email);
                                $sign_document_email->subject('Document Signing Request');
                                $sign_document_email->template('sign_document_request', 'notification_email_layout')
                                        ->viewVars(array('document_signing_link' =>
                                            Router::url(array('controller' => 'documents',
                                                'action' => 'sign',
                                                "?" => [
                                                    "userid" => $userdata['User']['id']
                                                    , "token" => $token
                                                    , "docuid" => $docudata['Document']['id']])
                                                    , true),
                                            'name_of_user' => $userdata['User']['name']));
                                $sign_document_email->send();

                            endforeach;
                            $this->Session->setFlash(__('Document uploaded and emails sent to all the signatories successfully.'), 'flash_success');
                        }
                        else {
                            $this->Session->setFlash(__('Document uploaded successfully.'), 'flash_success');
                        }

                        $this->set('document', $this->Document->findById($this->Document->id));
                    } else {
                        $this->Session->setFlash(__('Your document couldn\'t be uploaded.'), 'flash_error');
                    }
                } else {
                    $this->Session->setFlash(__('Your document couldn\'t be uploaded.'), 'flash_error');
                }
            }
        }
    }

    public function sign() {

        if ($this->request->is('get')) {
            /*
              Check if the entered url is valid or not
             */
            if (isset($this->params['url']['token']) && isset($this->params['url']['userid']) &&
                    isset($this->params['url']['docuid'])) {
                /*
                  Checking if the current logged in user is the user that was requested to sign the document.
                  Otherwise logout the current user and ask him to login again with the account which was requested to
                  sign
                 */
                if (AuthComponent::user('id')) {
                    if (CakeSession::read("Auth.User.id") === $this->params['url']['userid']) {
                        $token = $this->params['url']['token'];
                        $userid = $this->params['url']['userid'];
                        $docuid = $this->params['url']['docuid'];
                    } else {
                        $this->Auth->logout();
                        $this->Session->setFlash(__('Please login with your account to sign the document.'), 'flash_warning');
                        return $this->redirect(array('controller' => 'users', 'action' => 'index'));
                    }
                }
            } else {
                throw new NotFoundException(__('Invalid URL'));
            }

            /*
              If URL is valid check whether presented variables in URL are present in databse or not
             */
            $this->loadModel('Col');
            $parameters = array(
                'conditions' => array(
                    'uid' => $userid,
                    'token' => $token,
                    'did' => $docuid
                ),
                'fields' => array('id', 'status')
            );
            $coldata = $this->Col->find('first', $parameters);

            if ($coldata) {
                /*
                  If all the variables are correct show the document to the user and render the view
                 */
                $this->set('document', $this->Document->findById($docuid));
                $this->render();
                /*
                  Add code here to change the token so that the URL is no longer valid
                 */
            } else {
                $this->Session->setFlash(__('Invalid Request.Please ask the document owner to resend you the signing email
                                    '), 'flash_error');
                return $this->redirect(array('controller' => 'dashboard', 'action' => 'index'));
            }
        }

        if ($this->request->is('post')) {
            /*
              Storing these values in variables as data would be unset so that these fields dont get saved while
              updating the col and document tables
             */
            $status = $this->request->data['status'];
            $docuid = $this->request->data['docuid'];

            $this->loadModel('Col');
            $parameters = array(
                'conditions' => array(
                    'uid' => $this->request->data['userid'],
                    'did' => $this->request->data['docuid']
                ),
                'fields' => array('id', 'status')
            );
            $coldata = $this->Col->find('first', $parameters);

            /*
              Set the col id so that it can be updated
             */
            $this->Col->id = $coldata['Col']['id'];
            $this->Col->set('status', $userdata['User']['id']);

            /*
              Unset these variables so that they dont get saved.
             */
            unset($this->request->data['userid']);
            unset($this->request->data['docuid']);

            if ($this->Col->save($this->request->data)) {
                $total_collabarators = $this->Col->find('count', array('conditions' => array('did' => $docuid)));
                /*
                  See here if he document has to be rejected even if one user rejects it or not,
                  Checking if current user voided or rejected the document
                 */
                if ($status === Configure::read('doc_void') || $status === Configure::read('doc_rejected')) {
                    /*
                      Even if one user voids or rejects the document whole document is voided or rejected
                     */
                    $this->Document->id = $docuid;
                    $this->Document->set('status', $status);
                    $this->Document->save();
                }
                /*
                  Checking if current user signed the document.
                 */ elseif ($status === Configure::read('doc_completed')) {
                    /*
                      If all the signatories sign the document than only document will have status complete i.e. 1
                     */
                    $parameters = array(
                        'conditions' => array(
                            'did' => $docuid,
                            'status' => Configure::read('doc_completed')
                        )
                    );
                    $collabarators_with_completed_status = $this->Col->find('count', $parameters);

                    /*
                      Checking if document signing has been completed or not.
                     */
                    if ($collabarators_with_completed_status === $total_collabarators) {
                        $this->Document->id = $docuid;
                        $this->Document->set('status', "1");
                        $this->Document->save();
                    }
                }

                /*
                  Sending the notification email to the owner that there has been some changes in document.
                  Letting him to know to visit the dashboard
                  Will add the option in future to disable email alert for every status update.
                  Also include here to send the emails to all the other collabarators also to notify them of the change.
                 */
                $parameters = array(
                    'conditions' => array(
                        'id' => $docuid
                    ),
                    'fields' => array('ownerid')
                );
                $owner_id = $this->Document->find('first', $parameters);

                $parameters = array(
                    'conditions' => array(
                        'id' => $owner_id['Document']['ownerid']
                    ),
                    'fields' => array('username', 'name')
                );

                $owner_data = $this->User->find('first', $parameters);
                $document_change_email = new CakeEmail('mandrill_signup');
                $document_change_email->to($owner_data['User']['username']);
                $document_change_email->subject('Document Status Updated');
                $document_change_email->template('document_updated_request', 'notification_email_layout')
                        ->viewVars(array('dashboard_link' =>
                            Router::url(array('controller' => 'dashboard',
                                'action' => 'index'), true),
                            'name_of_user' => $owner_data['User']['name']));
                $document_change_email->send();

                $this->Session->setFlash(__('Your status updated successfully.
                                    '), 'flash_success');
            } else {
                $this->Session->setFlash(__('Error while saving your data.Please try again later.
                                    '), 'flash_error');
            }
        }
    }

}

?>
