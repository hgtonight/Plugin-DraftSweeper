<?php
/*	Copyright 2014-2016 Zachary Doll
 *	This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$PluginInfo['DraftSweeper'] = array(
   'Name' => 'Draft Sweeper',
   'Description' => 'A plugin that adds a link to sweep all drafts from the system, per user.',
   'Version' => '0.2',
   'RequiredApplications' => array('Vanilla' => '2.2'),
   'MobileFriendly' => true,
   'HasLocale' => true,
   'RegisterPermissions' => false,
   'SettingsUrl' => '/settings/draftsweeper',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => 'Zachary Doll',
   'AuthorEmail' => 'hgtonight@daklutz.com',
   'AuthorUrl' => 'http://www.daklutz.com',
   'License' => 'GPLv2',
   'GitHub' => 'hgtonight/Plugin-DraftSweeper',
);

class DraftSweeper extends Gdn_Plugin {

    public function settingsController_draftSweeper_create($sender) {
        $sender->Permission('Garden.Settings.Manage');
        $sender->addSideMenu('settings/draftsweeper');
        $sender->SetData('Title', $this->getPluginKey('Name'));
        $sender->SetData('PluginDescription', $this->GetPluginKey('Description'));
        $sender->Render($this->GetView('settings.php'));
    }
    
    public function draftsController_beforeRenderAsset_handler($sender) {
        if($sender->EventArguments['AssetName'] == 'Content' && $sender->DraftData->count()) {
            echo anchor(t('Clear all drafts'), '/drafts/sweep', ['class' => 'DraftSweeper Button Hijack Options']);
        }
    }
    
    public function draftsController_sweep_create($sender, $userRef) {
        $sender->permission('Garden.Settings.Manage');
        
        if(is_null($userRef) || !is_numeric($userRef)) {
            $userRef = Gdn::session()->UserID;
        }

        $sender->DraftModel->sweep($userRef);
        
        $sender->jsonTarget('.MyDrafts.Active .Aside', null, 'Remove');
        $sender->jsonTarget('.DraftSweeper', null, 'Remove');
        
        $empty = '<div class="Empty">' . t('You do not have any drafts.') . '</div>';
        $sender->jsonTarget('.Drafts .ContentColumn .DataList', $empty, 'ReplaceWith');
        $sender->informMessage(t('Your drafts have been swept!'));
        $sender->render('Blank', 'Utility', 'Dashboard');
    }
    
    public function draftModel_sweep_create($sender) {
        $userID = val(0, $sender->EventArguments, false);
        $sender->SQL->delete('Draft', array('InsertUserID' => $userID));
        $sender->updateUser($userID);
    }
}
