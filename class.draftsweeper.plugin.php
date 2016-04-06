<?php
/*	Copyright 2016 Zachary Doll
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
   'Version' => '0.4',
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
    
    public function draftsController_sweepOne_create($sender, $draftID, $transientKey) {
        $sender->View = 'Blank';
        $sender->ControllerName = 'Utility';
        $sender->ApplicationFolder = 'Dashboard';
        $sender->SweepOne = true;
        $sender->delete($draftID, $transientKey);
    }
    
    public function draftsController_render_before($sender) {
        if(property_exists($sender, 'SweepOne') && $sender->SweepOne) {
            $json = $sender->getJson();
            if(!array_key_exists('ErrorMessage', $json)) {
                $sender->informMessage(t('Draft cleared!'));
                $sender->jsonTarget('.DraftSweeper', null, 'Remove');
                $sender->jsonTarget('.CommentForm form textarea', 'sweepComment', 'Callback');
            }
        }
    }
    
    public function discussionController_render_before($sender) {
        $sender->addJsFile('sweeper.js', $this->getPluginFolder(false));
    }
    
    public function discussionController_beforeFormButtons_handler($sender) {
        $hasDraft = ($sender->Form->HiddenInputs['DraftID']) ? true : false;
        if($hasDraft) {
            $draftID = $sender->Form->HiddenInputs['DraftID'];
            $transientKey = Gdn::session()->transientKey();
            echo $this->renderClearDraftButton($draftID, $transientKey);
        }
    }
    
    public function postController_beforeCommentRender_handler($sender) {
        if($sender->EventArguments['Draft']) {
            $draftID = $sender->Form->HiddenInputs['DraftID'];
            $transientKey = Gdn::session()->transientKey();
            $sender->jsonTarget('.CommentForm .Buttons .DraftSweeper', null, 'Remove');
            $sender->jsonTarget('.CommentForm .Buttons', $this->renderClearDraftButton($draftID,$transientKey), 'Append');
        }
    }
    
    private function renderClearDraftButton($draftID, $transientKey) {
        return anchor(t('Clear Draft'), "/drafts/sweepone/$draftID/$transientKey", ['class' => 'DraftSweeper Button Hijack Options pull-left']);
    }
}
