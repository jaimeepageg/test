<?php

namespace Ceremonies\Repositories;

use Carbon\Carbon;
use Ceremonies\Models\Booking;
use Ceremonies\Models\Bookings\Choices;
use Ceremonies\Models\Bookings\ChoicesFile;
use Ceremonies\Models\Bookings\ChoicesQuestion;
use Ceremonies\Models\Task;
use Ceremonies\Services\ChoicesDbGenerator;
use Ceremonies\Services\ChoicesTemplates;
use Ceremonies\Services\Mail;
use Ceremonies\Traits\HandleFilesTrait;

class ChoicesRepository
{
    use HandleFilesTrait;

    public function getForm(Booking $booking)
    {


        if ($booking->choices) {
            $choices = $booking->choices;
        } else {
            $choices = $this->initForm($booking);
        }

        // While testing
//        ChoicesQuestion::all()->each->delete();
//        Choices::all()->each->delete();
//        $choices = $this->initForm($booking);

        return $this->fillChoices($choices);
    }

    /**
     * Initialise a choices form, setting up database
     * references for each form question.
     *
     * @param Booking $booking
     * @return Choices $choices
     */
    private function initForm(Booking $booking)
    {
        // Create new Choices model and prefill
        $choices = new Choices();
        $choices->form_name = $this->getFormName($booking->type);
        $choices->status = "InProgress";
        $choices->save();
        $booking->choices()->save($choices);
        (new ChoicesDbGenerator($choices))->generate();
        $this->prefillBasicData($booking, $choices);

        // Reload model before returning
        $choices->refresh();
        $choices->load("questions");

        // Mark booking as in progress
        $booking->markInProgress();

        return $choices;
    }

    /**
     * Finds the correct form based on the booking type
     *
     * @param $bookingType
     * @return string
     */
    private function getFormName($bookingType): string
    {
        $forms = [
            "Stat Marriage Stafford" => "statutoryCeremony",
            "RO Marriage Ceremony" => "traditionalCeremony",
            "Marriage Ceremony AP" => "traditionalCeremony",
            "Marriage Ceremony RO Standard" => "traditionalCeremony",
            "Stat Marriage Ceremony Church" => "statutoryCeremony",
            "RO Civil Partnership" => "cpTraditionalCeremony",
            "Civil Partnership Ceremony AP" => "cpTraditionalCeremony",
            "RO Vow Renew" => "vowRenewalCeremony",
            "AP Vow Renewal" => "vowRenewalCeremony",
            "Naming Ceremony RO" => "babyNaming",
            "Naming Ceremony AP" => "babyNaming",
            "Stat Marriage Ceremony RG Special Licence" => "statutoryCeremony",
            "Stat Civil Partnership ceremony Housebound/detained" => "statutoryCeremony",
            "Stat Marriage ceremony Housebound/detained" => "statutoryCeremony",
            "RO Ceremony 30mins" => "traditionalCeremony",
            "Marriage Ceremony Enhanced" => "enhancedCeremony",
            "Marriage Ceremony Standard" => "traditionalCeremony",
            "Marriage Ceremony RO Enhanced" => "enhancedCeremony",
            "Marriage Ceremony AP Enhanced" => "enhancedCeremony",
            "Marriage Ceremony AP Standard" => "traditionalCeremony",
            "Civil Ceremony Enhanced Gold" => "cpEnhancedCeremony",
            "Civil Ceremony Enhanced Silver" => "cpEnhancedCeremony",
            "Civil Ceremony AP Enhanced Gold" => "cpEnhancedCeremony",
            "Civil Ceremony AP Enhanced Silver" => "cpEnhancedCeremony",
        ];

        // Noticed whitespace either side of name in API response
        $bookingType = trim($bookingType);

        return $forms[$bookingType];
    }

    /**
     * Sets up blank versions of the fields in the
     * database ready for autosaving. Recursively
     * iterates through the from property returned
     * from a FormTemplate.
     *
     * @param Choices $choices
     * @return void
     */
    private function createFormFields(Choices $choices, array $fields, int $parentIndex = 0): void
    {

        if ($parentIndex === 0) {
            foreach($fields as $index => $step) {

                if (isset($step["sections"]) && count($step["sections"]) > 0) {
                    foreach ($step["sections"] as $section) {
                        $this->createFormFields($choices, $section["fields"], 1);
                    }
                } else {
                    foreach ($step["fields"] as $field) {
                        if (isset($field["name"])) {
//                        dd($field);
                            (new ChoicesQuestion([
                                "form_id" => $choices->id,
                                "name" => $field["name"],
                                "question" => $field["label"] ?? "",
                                "position" => $index,
                            ]))->save();
                        }

                        if (isset($field["options"]["fields"]) && count($field["options"]["fields"]) > 0) {
                            $this->createFormFields($choices, $field["options"]["fields"], $index + 1);
                        }
                    }
                }

            }
        } else {
            foreach ($fields as $index => $field) {
                if (isset($field["name"])) {
                    (new ChoicesQuestion([
                        "form_id" => $choices->id,
                        "name" => $field["name"],
                        "question" => $field["label"] ?? "",
                        "position" => $parentIndex + $index,
                    ]))->save();
                }

                if (isset($field["options"]["fields"]) && count($field["options"]["fields"]) > 0) {
                    $this->createFormFields($choices, $field["options"]["fields"], $parentIndex + $index + 1);
                }
            }
        }

    }

    /**
     * @param Choices $choices
     *
     * @return array
     */
    private function fillChoices(Choices $choices): array
    {
        // Load template from DB
        // Iterate over each field
        // Find associated field in choices->questions
        // Pull in value from if one is set.

        //		$template = ChoicesTemplate::where('form_name', $choices->form_name)->first();
        //		$fields = $template->getFields();
        $formName = $choices->form_name;
        $template = ChoicesTemplates::$formName();

        // Get the individual field names
        $fieldNames = array_map(function ($item) {
            return $item["name"] ?? null;
        }, $template["fields"]);

        // Load all the data in one go (instead of ~50 individual queries)
        $questions = ChoicesQuestion::where("form_id", $choices->id)
            ->whereIn("name", $fieldNames)
            ->get();

        $fields = collect($template["fields"])
            ->keyBy("name")
            ->toArray();

        // Debugging only: Use to check for duplicate fields in a form.
        // $duplicates = $this->duplicatesCheck($questions);
        // dd($formName, $duplicates);

        // TODO: Make this update the correct field in the $template['fields'] array
        // Fill the fields with a value
        foreach ($questions as $question) {
            $field = $fields[$question->name];
            if ($question->answer) {
                $field["value"] = $question->answer;
            }
            $fields[$question->name]["value"] = $question->answer;
        }

        $template["fields"] = $fields;

        return $template;
    }

    /**
     * Takes the files from a request and adds them to the filesystem.
     *
     * @param Choices $choices
     * @return array
     */
    public function addFilesToForm($choices, $question_name)
        {
            $this->directoryExists("choices");

            $fileRefs = [];
            foreach ($_FILES["files"]["name"] as $key => $name) {
                // Create new fake filename and save to filesystem
                $fileExtension = pathinfo($name, PATHINFO_EXTENSION);
                $newName = date("Ymdhis") . $key . "." . $fileExtension;
                $this->addToFilesystem(
                    $newName,
                    $_FILES["files"]["tmp_name"][$key]
                );

                // Create ref and store
                $fileRef = new ChoicesFile([
                    "form_id" => $choices->id,
                    "question_name" => $question_name,
                    "file_name" => $name,
                    "local_file_path" => "choices/" . $newName,
                    "created_at" => Carbon::now(),
                ]);
                $fileRef->save();
                $fileRefs[] = $fileRef->getPublicData();
            }

        // Attach refs to choices
        //        $choices->files()->saveMany($fileRefs);

        // Return array of data about files
        return $fileRefs;
    }

    /**
     * Deletes a choices form file and its associated
     * model.
     *
     * @param $fileId
     * @return bool
     */
    public function removeFile($fileId)
    {
        // FIXME: Deletes model but not file
        // Fails with success

        $file = ChoicesFile::where("id", $fileId)->first();
        $success = $this->deleteFile($file->local_file_path);
        if ($success) {
            $success = $file->delete();
        }

        return $success;
    }

    /**
     * Saves answers to a choices form.
     *
     * @param $booking
     * @param $data
     * @return array
     */
    public function saveAnswers($booking, $data, $isJson = false)
    {
        $updated = [];

        if ($isJson) {
            $data = $this->formatAnswerData($data);
        }

        foreach ($data as $key => $value) {
            // Only run if a value has been provided
            if ($value !== "") {
                $question = $booking->choices->questions->firstWhere(
                    "name",
                    $key
                );

                // Only update field if a new value
                if ($question && $question->answer !== $value) {
                    $question->answer = $value;
                    $question->save();
                    $updated[] = $question;
                }
            }
        }

        return $updated;
    }

    /**
     * Notify admin a form has been completed.
     *
     * @param Choices $choices
     * @return void
     */
    public function notifyAdminComplete(Choices $choices)
    {
        $mailable = Mail::create("Choices Submitted")
            ->sendToOffice($choices->booking->office)
            ->with($choices->booking->toArray())
            ->send();

        return $mailable->sent;
    }

    public function notifyUserComplete(Choices $choices)
    {
        $mailable = Mail::create("Ceremony Choices Submitted")
            ->sendTo($choices->booking->email_address)
            ->with($choices->booking->toArray())
            ->send();

        return $mailable->sent;
    }

    /**
     * Mark the choices form task as complete.
     *
     * @param Booking $booking
     * @return void
     */
    public function markTaskForReview(Booking $booking)
    {
        $task = $booking->tasks->firstWhere("name", Task::$submitChoicesName);
        $task->markAsReview();
    }

    public function formatQuestions(Choices $choices)
    {
        // Fields to ignore
        $blacklist = ["contact_email_address"];

        $questions = $choices->questions->map(function ($question) use (
            $choices,
            $blacklist
        ) {
            // Format answers
            if ($question->answer) {
                if (str_contains($question->answer, "files:")) {
                    // files:1,2,3,4,5
                    // Fetch file
                    $question->answer = $this->getFilesFromAnswer(
                        $question->answer
                    );
                } elseif (!in_array($question->name, $blacklist)) {
                    $answer = ucfirst($question->answer);
                    $answer = str_replace("_", " ", $answer);
                    $question->answer = $answer;
                }
            }

            /**
             * Drop values into dynamic labels.
             *
             * Intentionally fixed to only work with couple types
             * and names as these are the only planned dynamic
             * values.
             */
            if (str_contains($question->question, "{{")) {
                // Get couple types
                $coupleType = $choices->booking->getCoupleType();
                if ($coupleType) {
                    [$partnerOneType, $partnerTwoType] = explode(
                        " and ",
                        $coupleType
                    );
                } else {
                    $partnerOneType = "Bride";
                    $partnerTwoType = "Groom";
                }

                if ($partnerOneType === $partnerTwoType) {
                    $partnerOneType .= " (Partner One)";
                    $partnerTwoType .= " (Partner Two)";
                }

                // Get partner names
                [$partnerOneName, $partnerTwoName] = $choices->getSubmittedNames();

                $question->question = str_replace(
                    "{{partnerOneType}}",
                    $partnerOneType,
                    $question->question
                );
                $question->question = str_replace(
                    "{{partnerTwoType}}",
                    $partnerTwoType,
                    $question->question
                );
                if ($partnerOneName) {
                    $question->question = str_replace(
                        "{{partner_one_name}}",
                        $partnerOneName,
                        $question->question
                    );
                }
                if ($partnerTwoName) {
                    $question->question = str_replace(
                        "{{partner_two_name}}",
                        $partnerTwoName,
                        $question->question
                    );
                }
            }

            return $question;
        });

        return $questions->filter(function ($question) {
            return $question->answer !== null;
        });
    }

    private function getFilesFromAnswer(string $answer)
    {
        $fileIds = str_replace("files:", "", $answer);
        $ids = explode(",", $fileIds);
        $files = ChoicesFile::whereIn("id", $ids)->get();

        $fileTags = "";

        foreach ($files as $file) {
            $fileTags .= sprintf(
                '<a href="%s" target="_blank" class="block">File: %s</a>',
                $file->getPublicUrl(),
                $file->file_name
            );
        }

        return $fileTags;
    }

    public function sendRejectionEmail(Booking $booking, Task $choicesTask)
    {
        header("Content-Type: text/html; charset=utf-8");
        $mailable = Mail::create("Ceremony Choices Require Attention")
            ->sendTo($booking->email_address)
            ->with(["booking" => $booking, "reason" => $choicesTask->note, "reg_office_email" => $booking->getRegOfficeEmail()])
            ->send();

        return $mailable->sent;
    }

    public function sendApprovalEmail(Booking $booking)
    {
        $mailable = Mail::create("Ceremony Choices Approved")
            ->sendTo($booking->email_address)
            ->with(['reg_office_email' => $booking->getRegOfficeEmail()])
            ->send();

        return $mailable->sent;
    }

    /**
     * Formats the answer data from the JSON request
     * to match a typical formData request.
     *
     * @param $answerData
     * @return array
     */
    public function formatAnswerData($answerData): array
    {
        $formatted = [];

        foreach ($answerData as $answer) {
            $formatted[$answer["name"]] = $answer["value"];
        }

        return $formatted;
    }

    /**
     * Debugging ONLY. Will return a grouped list
     * of all fields in the form that have the same
     * name. No duplicates name attributes can be
     * present in a form.
     *
     * @param $questions
     * @return array
     */
    private function duplicatesCheck($questions): array
    {
        $duplicates = [];
        $questions->map(function ($question) use (&$duplicates) {
            $duplicates[$question->name][] = $question;
        });
        foreach ($duplicates as $name => $questions) {
            if (count($questions) === 1) {
                unset($duplicates[$name]);
            }
        }
        return $duplicates;
    }

    private function prefillBasicData(Booking $booking, Choices $choices)
    {

        // Name, home or mobile phone and email to be prefilled
        // The key should match to a field in the choices form
        // question_name => App\Models\Booking->fieldName
        $prefillableQuestions = [
            'name' => null,
            'contact_name' => null,
            'home_telephone' => 'phone_number',
            'mobile_telephone' => 'phone_number',
            'email_address' => 'email_address',
            'contact_email_address' => 'email_address'
        ];

        foreach ($prefillableQuestions as $question => $field) {

            $choicesQuestion = $choices->questions->where('name', $question)->first();
            if ($choicesQuestion && $field !== null) {
                $choicesQuestion->answer = $booking->$field;
                $choicesQuestion->save();
            } else if($choicesQuestion) {
                $mainClient = $booking->clients->where('is_primary', 1)->first();
                $choicesQuestion->answer = sprintf('%s %s', $mainClient->first_name, $mainClient->last_name);
                $choicesQuestion->save();
            }
        }

    }

}
