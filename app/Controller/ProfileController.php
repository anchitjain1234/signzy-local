<?php

class ProfileController extends AppController {

    public $layout = 'insidelayout';
    public $helpers = array('UploadPack.Upload');

    public function beforeFilter() {
        /*
          To give the name ofthe user to the layout befire the view is loaded.
         */
        $this->loadModel('User');

        $uid = CakeSession::read('Auth.User.id');
        $params = array(
            'conditions' => array('id' => $uid),
        );
        $userdata = $this->User->find('first', $params);
        $this->set('name', $userdata['User']['name']);
        $this->set('userdata', $userdata);
    }

    public function index() {
        $this->loadModel('Document');
        $params = array(
            'conditions' => array('ownerid' => CakeSession::read('Auth.User.id')),
            'limit' => 5,
            'order' => array('created' => -1),
        );
        $this->set('uploads', $this->Document->find('all', $params));

        $params = array(
            'conditions' => array('ownerid' => CakeSession::read('Auth.User.id'), 'status' => Configure::read('doc_rejected')),
            'limit' => 5,
            'order' => array('created' => -1),
        );
        $this->set('disputeduploads', $this->Document->find('all', $params));
    }

    public function verification() {
        if ($this->request->is('get')) {
            $previous_existence_check = $this->Profile->find('first', array('conditions' => array('uid' => CakeSession::read('Auth.User.id'))));
            if ($previous_existence_check) {
                if ($previous_existence_check['Profile']['verified'] === Configure::read('profile_rejected')) {
                    $this->render();
                } elseif ($previous_existence_check['Profile']['verified'] === Configure::read('profile_unverified')) {
                    /*
                     * If user application is in provisioning.
                     */
                    $this->Session->setFlash('Your profile is in verification process.Pleasae wait for it to finish.', 'flash_error');
                    return $this->redirect(array('controller' => 'profile', 'action' => 'index'));
                } elseif ($previous_existence_check['Profile']['verified'] === Configure::read('profile_verified')) {
                    /*
                     * If user application has been verified before.
                     */
                    $this->Session->setFlash('Your profile is already verified.', 'flash_error');
                    return $this->redirect(array('controller' => 'profile', 'action' => 'index'));
                }
            }
        }
    }

    public function profilepicture() {
        
    }

    public function imgupload() {
        $this->request->allowMethod('ajax');
        $this->autorender = false;
        $this->layout = false;
//        print_r($_FILES);

        $allowed = array('jpg', 'png', 'jpeg');
        if (isset($_FILES['data']['name']['Profile']['file']) && $_FILES['data']['error']['Profile']['file'] == 0) {
            $extension = pathinfo($_FILES['data']['name']['Profile']['file'], PATHINFO_EXTENSION);

            if (!in_array(strtolower($extension), $allowed)) {
                echo '{"documentstatus" : false , "error": -1}';
                exit;
            }

            $temporary_document_name = $this->get_temporary_document_name();
            $temporary_document_name = $temporary_document_name . "." . $extension;

            if (move_uploaded_file($_FILES['data']['tmp_name']['Profile']['file'], Configure::read('image_upload_location') . $temporary_document_name)) {

                $aws_sdk = $this->get_aws_sdk();
//                    $s3_client = $aws_sdk->createS3();
                $sqs_client = $aws_sdk->createSqs();

                $uploading_queue_localhost = $sqs_client->createQueue(array('QueueName' => Configure::read('upload_queue')));
                $uploading_queue_localhost_url = $uploading_queue_localhost->get('QueueUrl');

                $sqs_client->setQueueAttributes(array(
                    'QueueUrl' => $uploading_queue_localhost_url,
                    'Attributes' => array(
                        'VisibilityTimeout' => 5,
                        'ReceiveMessageWaitTimeSeconds' => 5,
                        'DelaySeconds' => 0
                    ),
                ));

//                    $this->add_upload_message_sqs($temporary_document_name, $sqs_client, $uploading_queue_localhost_url);
//                    $this->log('going to upload into s3');
//                    $this->upload_s3_from_sqs();
//                    $this->log('exiting from s3');

                echo '{"documentstatus" : true '
                . ',"documentname" : "' . $temporary_document_name . '"'
                . ',"documentsize":' . $_FILES['data']['size']['Profile']['file'] . ''
                . ',"documentoriginalname":"' . $_FILES['data']['name']['Profile']['file'] . '"'
                . ',"documenttype":"' . $_FILES['data']['type']['Profile']['file'] . '"}';
                exit;
            }
            /*
             * If document has been uploaded to server but cannot be moved to safe location.
             */
            echo '{"documentstatus" : false , "error":' . $_FILES['data']['error']['Document']['file'] . '}';
        }
        /*
         * Case when document could not be uploaded completely on our server which could be due to size limitations or some
         * other problem.
         */
        echo '{"documentstatus" : false }';
    }

    /*
     * Add code here in preview retrive images from s3 bucket if not present locally in server.
     */

    public function preview($imgname = null) {
        $this->layout = false;

        if (!$imgname) {
            throw new NotFoundException(__('Invalid Image'));
        }

        $imgurl = Configure::read('image_upload_location') . $imgname;
        $file_check = file_exists($imgurl);

        if ($file_check) {
            $ntct = Array("1" => "image/gif",
                "2" => "image/jpeg",
                "3" => "image/png",
                "6" => "image/bmp",
                "17" => "image/ico");
            header('Content-type: ' . $ntct[exif_imagetype($imgurl)]);
//            $extension = strtolower(pathinfo($imgname, PATHINFO_EXTENSION));
//            
//            switch ($extension) {
//                case "gif": $ctype = "image/gif";
//                    break;
//                case "png": $ctype = "image/png";
//                    break;
//                case "jpeg":
//                case "jpg": $ctype = "image/jpeg";
//                    break;
//                default:
//            }
//            header('Content-type: ' . $ctype);
//            header("Content-Length: " . filesize($imgurl));
            readfile($imgurl);
        } else {
            throw new NotFoundException(__('Invalid URL'));
        }
    }

    public function facefromcard() {
        $this->request->allowMethod('ajax');
        $this->autorender = false;
        $this->layout = false;

        if ($this->request->is('post')) {
            $imgname = $this->request->data['imgname'];
            $command = "python " . Configure::read('python_scripts_location') . "test.py " . Configure::read('image_upload_location') . $imgname;
            $output = array();
            $res = system($command, $output);
            echo $res;
        }
    }

    public function submitverification() {
        $this->request->allowMethod('ajax');
        $this->autorender = false;
        $this->layout = false;

        if ($this->request->is('post')) {
            $imgname = $this->request->data['name'];

            /*
             * Checking if the user has applied before for profile verification and has been rejected
             */

            $previous_existence_check = $this->Profile->find('first', array('conditions' => array('uid' => CakeSession::read('Auth.User.id'))));
            if ($previous_existence_check) {
                if ($previous_existence_check['Profile']['verified'] === Configure::read('profile_rejected')) {
                    $this->Profile->set('verified', Configure::read('profile_unverified'));
                } elseif ($previous_existence_check['Profile']['verified'] === Configure::read('profile_unverified')) {
                    /*
                     * If user application is in provisioning.
                     */
                    $this->Session->setFlash('Your profile is in verification process.Pleasae wait for it to finish.', 'flash_error');
                    return $this->redirect(array('controller' => 'profile', 'action' => 'index'));
                } elseif ($previous_existence_check['Profile']['verified'] === Configure::read('profile_verified')) {
                    /*
                     * If user application has been verified before.
                     */
                    $this->Session->setFlash('Your profile is already verified.', 'flash_error');
                    return $this->redirect(array('controller' => 'profile', 'action' => 'index'));
                }
            } else {
                /*
                 * If user is applying for first time.
                 */
                $this->Profile->create();
                $this->Profile->set('uid', CakeSession::read('Auth.User.id'));
                $this->Profile->set('verified', Configure::read('profile_unverified'));
                $this->Profile->set('verificationimage', $imgname);
                $this->Profile->set('cardnumber', $this->request->data['cardnumber']);
            }
            if ($this->Profile->save()) {
                /*
                 * Add code here to send emails to support staff so that they can verify the profile.
                 */
                $aws_sdk = $this->get_aws_sdk();
                $sqs_client = $aws_sdk->createSqs();

                $email_queue_localhost = $sqs_client->createQueue(array('QueueName' => Configure::read('email_queue')));
                $email_queue_localhost_url = $email_queue_localhost->get('QueueUrl');

                $email_to_be_sent = array();
                $email_to_be_sent['link'] = Router::url(array('controller' => 'profile', 'action' => 'verifyuserprofile', '?' => ["uid" => CakeSession::read('Auth.User.id')]), true);
                $email_to_be_sent['title'] = 'Profile Approval';
                $email_to_be_sent['subject'] = 'Approval of Profile';
                $email_to_be_sent['content'] = 'Please click below link to verify the user profile.';
                $email_to_be_sent['button_text'] = 'Verify Profile';

                $this->loadModel('User');
                $support_staff = $this->User->find('all', array('conditions' => array('type' => Configure::read('support'))));

                foreach ($support_staff as $support) {
                    $this->add_email_message_sqs($email_to_be_sent, $sqs_client, $email_queue_localhost_url, $support);
                }

                echo '{"success":true}';
                $this->send_email_from_sqs();
                exit();
            }
            echo '{"error":1}';
            exit();
        }
        echo '{"error":2}';
        exit();
    }

    public function verifyuserprofile() {
        $this->loadModel('User');
        $userinfo = $this->User->find('first', array('conditions' => array('id' => CakeSession::read('Auth.User.id'))));

        if ($this->request->is('get')) {

            if ($userinfo['User']['type'] != Configure::read('customer')) {

                if (isset($this->params['url']['uid'])) {
                    $applier_id = $this->params['url']['uid'];
                } else {
                    throw new NotFoundException('Invalid URL format');
                }
                $applier_profile = $this->Profile->find('first', array('conditions' => array('uid' => $applier_id)));

                if (!$applier_profile) {
                    throw new NotFoundException('User not having profile');
                }

                $this->set('userinfo', $applier_profile);
            }
        }

        if ($this->request->is('post') && $userinfo['User']['type'] != Configure::read('customer')) {
            $this->request->allowMethod('ajax');
            $this->autorender = false;
            $this->layout = false;

            $option = $this->request->data['option'];
            $uid = $this->request->data['uid'];
            $pid = $this->request->data['pid'];

            $aws_sdk = $this->get_aws_sdk();
            $sqs_client = $aws_sdk->createSqs();

            $email_queue_localhost = $sqs_client->createQueue(array('QueueName' => Configure::read('email_queue')));
            $email_queue_localhost_url = $email_queue_localhost->get('QueueUrl');

            $this->loadModel('User');
            $userdata = $this->User->find('first', array('conditions' => array('id' => $uid)));

            if ($option == 'TRUE') {
                $this->Profile->id = $pid;
                $this->Profile->set('verified', Configure::read('profile_verified'));

                if ($this->Profile->save()) {
                    /*
                     * Add code here to send email to the user that he has been verified.
                     */

                    $email_to_be_sent = array();
                    $email_to_be_sent['link'] = Router::url(array('controller' => 'dashboard', 'action' => 'index'), true);
                    $email_to_be_sent['title'] = 'Profile Verified';
                    $email_to_be_sent['subject'] = 'Profile Verified';
                    $email_to_be_sent['content'] = 'Congrats your profile has been verified.You can now sign documents.'
                            . 'PLease click below button to visit your dashboard.';
                    $email_to_be_sent['button_text'] = 'Dashboard';
                    $this->add_email_message_sqs($email_to_be_sent, $sqs_client, $email_queue_localhost_url, $userdata);

                    echo '{"success":1}';
                    $this->send_email_from_sqs();
                    exit();
                }
                echo '{"error":1}';
                exit();
            } else {
                $this->Profile->id = $pid;
                $this->Profile->set('verified', Configure::read('profile_rejected'));

                if ($this->Profile->save()) {
                    /*
                     * Add code here to send email to the user that his verification has been rejected.However he can reapply again.
                     */

                    $email_to_be_sent = array();
                    $email_to_be_sent['link'] = Router::url(array('controller' => 'profile', 'action' => 'index'), true);
                    $email_to_be_sent['title'] = 'Profile Rejected';
                    $email_to_be_sent['subject'] = 'Profile Rejected';
                    $email_to_be_sent['content'] = 'Sorry we cant verify the details you provided but you can reapply again.'
                            . 'PLease click below button to reapply for verification.';
                    $email_to_be_sent['button_text'] = 'Your Profile';
                    $this->add_email_message_sqs($email_to_be_sent, $sqs_client, $email_queue_localhost_url, $userdata);

                    echo '{"success":2}';
                    $this->send_email_from_sqs();
                    exit();
                }
            }
        }
    }

}
