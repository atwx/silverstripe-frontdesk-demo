<?php

namespace App\Controllers;

use App\Models\Project;
use App\Models\ProjectNote;
use Atwx\SilverstripeFrontdeskKit\Controller\StandaloneRecordController;
use Atwx\SilverstripeFrontdeskKit\Forms\FieldLayout;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

// [demo:standalone]
/**
 * Feedback of the client on a project, without an account: the link holds
 * the project's token (feedback/<token>). Only the feedback fields can be
 * written; the rest of the project is shown read-only or not at all.
 *
 */
class FeedbackController extends StandaloneRecordController
{
    public static function linkFor(Project $project): string
    {
        return Director::absoluteURL('feedback/' . $project->FeedbackToken);
    }

    protected function resolveRecord(HTTPRequest $request): ?DataObject
    {
        $token = (string) $request->param('Key');
        return strlen($token) === 32 ? Project::get()->find('FeedbackToken', $token) : null;
    }

    protected function baseLink(): string
    {
        return 'feedback/' . $this->getRequest()->param('Key');
    }

    protected function defineFieldLayout(DataObject $record): FieldLayout
    {
        return FieldLayout::create()
            ->group('Project', ['Title', 'StartDate', 'EndDate'])
            ->group('Your feedback', ['FeedbackRating', 'FeedbackText'])
            ->setDefaultLevel(FieldLayout::HIDDEN)
            ->setLevels([
                'Title' => FieldLayout::READ,
                'StartDate' => FieldLayout::READ,
                'EndDate' => FieldLayout::READ,
                'FeedbackRating' => FieldLayout::WRITE,
                'FeedbackText' => FieldLayout::WRITE,
            ]);
    }

    protected function formFields(FieldList $fields, DataObject $record): FieldList
    {
        $fields->replaceField('FeedbackRating', DropdownField::create('FeedbackRating', 'How happy are you?', [
            5 => '★★★★★ Very happy',
            4 => '★★★★ Happy',
            3 => '★★★ OK',
            2 => '★★ Not quite',
            1 => '★ Unhappy',
        ])->setEmptyString('–'));
        return $fields;
    }

    protected function pageTitle(DataObject $record): string
    {
        return 'Feedback: ' . $record->getTitle();
    }

    protected function introText(DataObject $record): string
    {
        return 'Thank you for working with us. Tell us how the project went – no account needed.';
    }

    protected function afterSave(DataObject $record, array $changed): void
    {
        /** @var Project $record */
        if (!$changed) {
            return;
        }
        $record->FeedbackAt = date('Y-m-d H:i:s');
        $record->write();
        ProjectNote::create([
            'ProjectID' => $record->ID,
            'Kind' => 'feedback',
            'Text' => $record->FeedbackRating . '/5' . ($record->FeedbackText ? ': ' . $record->FeedbackText : ''),
        ])->write();
    }
}
// [/demo:standalone]
