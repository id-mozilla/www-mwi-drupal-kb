<?php
// $Id: shared_edit.pages.inc,v 1.3 2009/06/18 13:38:15 mfer Exp $
/**
 * @file
 * Contains the page callbacks for shared edit.
 */

/**
 * Callback: Shared Edit add/remove form.
 */
function shared_edit_access_form() {
  $form = array();
  $node = node_load(arg(1));
  $result = db_query('SELECT se.uid, u.name FROM {shared_edit} se LEFT JOIN {users} u ON se.uid = u.uid WHERE se.nid = %d', $node->nid);
  $form['users']['user_'. $node->uid]['name'] = array('#value' => l($node->name, 'user/'. $node->uid));
  $form['users']['user_'. $node->uid]['operation'] = array('#value' => '<em>'. t('locked') .'</em>');
  while ($account = db_fetch_object($result)) {
    // Only list a user once. If they are in shared edit and the node owner do not list
    // them a second time.
    if ($account->uid != $node->uid) {
      $form['users']['user_'. $account->uid]['name'] = array('#value' => l($account->name, 'user/'. $account->uid));
      $form['users']['user_'. $account->uid]['operation'] = array('#value' => l(t('delete'), 'node/'. $node->nid .'/shared_edit/remove/'. $account->uid));
    }
  }

  $form['shared_edit_add_new'] = array(
    '#id' => 'shared_edit_add_new',
    '#type' => 'textfield',
    '#size' => 30,
    '#maxlength' => 255,
    '#required' => TRUE,
    '#autocomplete_path' => 'user/autocomplete',
  );
  $form['shared_edit_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add access'),
  );
  return $form;
}

/**
 * Theme the form in a table similar to drupal.org cvs access page.
 */
function theme_shared_edit_access_form($form) {
  $header = array(
    array('data' => t('User')),
    array('data' => t('Operations')),
  );
  $rows = array();
  foreach (element_children($form['users']) as $key) {
    $row = array();
    $row['data'][] = drupal_render($form['users'][$key]['name']);
    $row['data'][] = drupal_render($form['users'][$key]['operation']);
    $rows[] = $row;
  }
  $row = array();
  $row['data'][] = drupal_render($form['shared_edit_add_new']);
  $row['data'][] = drupal_render($form['shared_edit_submit']);
  $rows[] = $row;
  $output = theme('table', $header, $rows);
  $output .= drupal_render($form);
  return $output;
}

function shared_edit_access_form_validate($form, &$form_state) {
  $name = $form_state['values']['shared_edit_add_new'];
  $node = node_load(arg(1));
  $account = user_load(array('name' => $name));
  if ($node->name == $name) {
    form_set_error('shared_edit_add_new', t('The username is already the content owner.'));
  }
  else if (!empty($account) && db_result(db_query('SELECT count(*) FROM {shared_edit} WHERE nid = %d AND uid = %d', $node->nid, $account->uid))) {
    form_set_error('shared_edit_add_new', t('The username already has edit access to the content.'));
  }
  else if (empty($account)) {
    form_set_error('shared_edit_add_new', t('Please enter a valid username.'));
  }
}

function shared_edit_access_form_submit($form, &$form_state) {
  $account = user_load(array('name' => $form_state['values']['shared_edit_add_new']));
  $node = node_load(arg(1));
  if (!shared_edit_add($node->nid, $account->uid)) {
    // There was an error. Throw a message.
    drupal_set_message(t('The user %user could not be added. Please see a website administrator for more details.', array('%user' => $account->name)), 'error');
  }
}

/**
 * Menu callback: remove a users access to edit a node.
 */
function shared_edit_remove_user() {
  $node = node_load(arg(1));
  $account = user_load(array('uid' => arg(4)));
  if (!shared_edit_remove($node->nid, $account->uid)) {
    // There was an error. Throw a message.
    drupal_set_message(t('The user %user could not be removed. Please see a website administrator for more details.', array('%user' => $account->name)), 'error');
  }
  drupal_goto('node/'. $node->nid .'/shared_edit');
}