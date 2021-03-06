<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for FiveThirtySeven */
class CRM_Upgrade_Incremental_php_FiveThirtySeven extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
    if ($rev === '5.37.alpha1') {
      $preUpgradeMessage .= '<p>' . ts('Some mail-merge tokens may display differently when used with Scheduled Reminders, Mosaico templates, or PDF letters. For details, see <a href="%1" target="_blank">upgrade notes</a>.',
          [1 => 'https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/#token-format']) . '</p>';
    }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_37_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('core-issue#1845 - Alter Foreign key on civicrm_group to delete when the associated group when the saved search is deleted', 'alterSavedSearchFK');
    $this->addTask('core-issue#2243 - Add note_date to civicrm_note', 'addColumn',
     'civicrm_note', 'note_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'Date attached to the note'");
    $this->addTask('core-issue#2243 - Add created_date to civicrm_note', 'addColumn',
     'civicrm_note', 'created_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When the note was created'");

    $this->addTask('core-issue#2243 - Update existing note_date and created_date', 'updateNoteDates');
  }

  //  /**
  //   * Upgrade function.
  //   *
  //   * @param string $rev
  //   */
  //  public function upgrade_5_0_x($rev) {
  //    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  //    $this->addTask('Do the foo change', 'taskFoo', ...);
  //    // Additional tasks here...
  //    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
  //    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  //  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateNoteDates(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_note SET note_date = modified_date, created_date = modified_date, modified_date = modified_date");
    return TRUE;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function alterSavedSearchFK(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_group', 'FK_civicrm_group_saved_search_id');
    CRM_Core_DAO::executeQuery('DELETE civicrm_saved_search FROM civicrm_saved_search LEFT JOIN civicrm_group ON civicrm_saved_search.id = civicrm_group.saved_search_id WHERE civicrm_group.id IS NULL AND form_values IS NOT NULL and api_params IS NULL');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_group ADD CONSTRAINT `FK_civicrm_group_saved_search_id` FOREIGN KEY (`saved_search_id`) REFERENCES `civicrm_saved_search`(`id`) ON DELETE CASCADE', [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

}
