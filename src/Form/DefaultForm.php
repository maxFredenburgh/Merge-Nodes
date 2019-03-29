<?php

namespace Drupal\mergenodes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultForm.
 */
class DefaultForm extends FormBase {


    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'mergeNodesForm';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $session = \Drupal::request()->getSession();
        $form['contenttype'] = [
            '#type' => 'select',
            '#title' => $this->t('Select a content type to merge'),
            '#options' => ['app' => $this->t('App'), 'page' => $this->t('Basic page'), 'blog_post' => $this->t('Blog post'), 'commitment' => $this->t('Commitment'), 'consultation' => $this->t('Consultation'), 'idea' => $this->t('Idea'), 'landing_page' => $this->t('Landing page'), 'suggested_dataset' => $this->t('Suggested dataset'), 'webform' => $this->t('Webform')],
            '#size' => 5,
            '#weight' => '0',
        ];
        $form['alt_button'] = array(
            '#type' => 'submit',
            '#value' => t('View Node Mappings'),
            '#name' => 'view_mappings',
            '#submit' => array([$this, 'viewMappings']),
        );
            $form['node_translations'] = [
                '#type' => 'table',
                '#header' => [
                    $this->t('Default Node (English)'),
                    $this->t('Translation Node (French)')
                ],
                '#rows' => [],
            ];

        if(!empty($session->get('contenttype'))){
            $nids = \Drupal::entityQuery('node')->condition('type',$session->get('contenttype'))->execute();
            $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
            foreach ($nodes as $node){
                if($node->get('langcode')->value=='en'){
                    $this->node = $node;
                    foreach ($nodes as $no){
                        if(($no->get('field_previous_id')->value == ($node->get('field_previous_id')->value))
                            and ($no->get('langcode')->value=='fr')){
                            $form['node_translations'][$node->get('title')->value]['node_source']=['#markup' => $this->t($node->get('title')->value)] ;
                            $form['node_translations'][$node->get('title')->value]['node_trans']=['#markup' => $this->t($no->get('title')->value)];
                        }
                    }
                }
            }
        }

        $form['submit'] = [
            '#type' => 'submit',
            '#title' => $this->t('Merge'),
            '#value' => $this->t('Submit'),
        ];

        return $form;
    }
    public function viewMappings(array &$form, FormStateInterface &$form_state) {
        $session = \Drupal::request()->getSession();
        $session->set('contenttype',$form_state->getValue('contenttype'));
        $form_state->setRebuild();
    }
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Display result.
        /*foreach ($form_state->getValues() as $key => $value) {
          drupal_set_message($key . ': ' . $value);
        }*/
        $contenttype = $form_state->getValue('contenttype');

        if ($contenttype) {
            $nids = \Drupal::entityQuery('node')->condition('type',$contenttype)->execute();
            $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);

            foreach ($nodes as $node){
                if($node->get('langcode')->value=='en'){
                    $this->node = $node;
                    foreach ($nodes as $no){
                        if(($no->get('field_previous_id')->value == ($node->get('field_previous_id')->value))
                                and ($no->get('langcode')->value=='fr')){
                            $this->mergeTranslations($no, 'fr');
                            $this->removeNode($no);
                            $this->node->save();
                        }
                    }
                }
            }
        }
        else {
            drupal_set_message("No content type selected", 'warning');
        }

    }
    private function removeNode($node_source) {
        try {
            // $this->messenger->addStatus($this->t('Node @node has been removed.', ['@node' => $node_source->getTitle()]));
            $node_source->delete();

            return TRUE;
        }
        catch (\Exception $e) {
            return $e;
        }
    }
    private function mergeTranslations($node_source, $langcode) {
        //$languages = $this->languages->getLanguages();
        //wrapper for all node sources
        //run this process for all english nodes with translation source as the french nodes


            $this->addTranslation($langcode, $node_source->toArray());

    }
    private function addTranslation($langcode, array $node_array) {

        $node_target = $this->node;
        $message_argumens = [
            '@langcode' => $langcode,
            '@title' => $node_target->getTitle(),
        ];

        if (!$node_target->hasTranslation($langcode)) {
            $node_target->addTranslation($langcode, $node_array);
            return TRUE;
        }

        return FALSE;
    }

}
