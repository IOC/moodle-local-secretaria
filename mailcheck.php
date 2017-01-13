<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/soaplib.php');
require_once($CFG->libdir . '/xtecmail/lib.php');
require_once($CFG->libdir . '/formslib.php');

admin_externalpage_setup('local_secretaria/mailcheck');

require_capability('moodle/site:config', context_system::instance());

class mailcheck_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('text', 'sendemail', get_string('email', 'moodle'));
        $mform->setType('sendemail', PARAM_EMAIL);

        $this->add_action_buttons(false, get_string('submit'));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mailcheck', 'local_secretaria'));

if (!empty($CFG->local_xtecmail_app)) {
    $mform = new mailcheck_form();

    $xm = new xtecmail($CFG->local_xtecmail_app,
                       $CFG->local_xtecmail_sender,
                       $CFG->local_xtecmail_env);

    if ($data = $mform->get_data()) {
        $mail = array(
            'to' => array($data->sendemail),
            'from' => $CFG->noreplyaddress,
            'subject' => 'Correu de test de ' . $CFG->local_xtecmail_env,
            'body' => 'Correu de test enviat ' . userdate(time()),
            'contenttype' => 'text/plain',
         );
        try {
            $xm->send($mail['to'], array(), array(), $mail['from'], $mail['subject'],
                      $mail['body'], $mail['contenttype'], array());
            $output = html_writer::tag('div', get_string('mailsent', 'message'));
        } catch (xtecmailerror $e) {
            $output = html_writer::tag('div', $e->getMessage());
        }
    } else {
        $output = html_writer::tag('div', 'local_xtecmail_app: ' . $CFG->local_xtecmail_app);
        $output .= html_writer::tag('div', 'local_xtecmail_sender: ' . $CFG->local_xtecmail_sender);
        $output .= html_writer::tag('div', 'local_xtecmail_env: ' . $CFG->local_xtecmail_env);
        try {
            $output .= html_writer::start_div();
            $output .= get_string('status', 'moodle') . ': ';
            if ($xm->test()) {
                $output .= html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/check')));
            } else {
                $output .= html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/warning')));
            }
            $output .= html_writer::end_div();
        } catch (xtecmailerror $e) {
            $output .= html_writer::tag('div', $e->getMessage());
        }
    }
    echo $output;

    $mform->display();

}

echo $OUTPUT->footer();