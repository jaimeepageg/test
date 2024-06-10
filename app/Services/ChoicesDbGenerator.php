<?php

namespace Ceremonies\Services;

use Ceremonies\Models\Bookings\ChoicesQuestion;

class ChoicesDbGenerator
{

    private int $index;
    private $template;
    private $choices;

    public function __construct($choices)
    {
        $this->index = 0;
        $this->template = ChoicesTemplates::{$choices->form_name}();
        $this->choices = $choices;
    }

    /**
     * Start the generation process for creating
     * DB entries for each form field.
     *
     * @return void
     */
    public function generate()
    {
        foreach ($this->template['form'] as $step) {
            if (isset($step["sections"]) && count($step["sections"]) > 0) {
                foreach ($step["sections"] as $section) {
                    $this->createFields($section['fields']);
                }
            } else {
                $this->createFields($step['fields']);
            }
        }
    }

    /**
     * Loops through an array of form fields, creating
     * fields and checking for subfields. *Recursively*
     *
     * @param array $fields
     * @return void
     */
    private function createFields(array $fields): void
    {

        foreach ($fields as $field) {
            if (isset($field["name"])) {
                $this->save($field);
            }
//            if ($field['name'] === 'will_be_accompanied') {
//                dd($field);
//            }
            if ($this->hasSubFields($field)) {
                foreach($field['options'] as $option) {
                    if (isset($option['fields'])) {
                        $this->createFields($option['fields']);
                    }
                }
            }
        }

    }

    /**
     * Increments the current index before returning
     * it.
     *
     * @return int
     */
    private function getIndex(): int
    {
        $this->index++;
        return $this->index;
    }

    /**
     * Checks if a field has any subfields present.
     *
     * @param array $field
     * @return bool
     */
    private function hasSubFields(array $field): bool
    {

        if (!isset($field["options"])) {
            return false;
        }

        if (count($field['options']) === 0) {
            return false;
        }

        return (boolean) array_filter($field['options'], function($item) {
            return isset($item['fields']) && is_array($item['fields']);
        });

    }

    /**
     * Saves a $field to the database as a
     * ChoicesQuestion.
     *
     * @param array $field
     * @return void
     */
    private function save(array $field): void
    {
        (new ChoicesQuestion([
            "form_id" => $this->choices->id,
            "name" => $field["name"],
            "question" => $field["label"] ?? "",
            "position" => $this->getIndex(),
        ]))->save();
    }

}