<?php

namespace Ceremonies\Services;

class FormBuilder
{
    /**
     * URL to the standard ceremony book hosted within WP.
     */
    public static string $STD_BOOK_URL = "https://staffordshireceremonies.co.uk/wp-content/uploads/Marriage-Traditional-Choices-2.pdf";

    /**
     * URL to the enhanced ceremony book hosted within WP.
     */
    public static string $ENHANCED_BOOK_URL = "https://staffordshireceremonies.co.uk/wp-content/uploads/Marriage-Enhanced-Choices-Readings-and-Promises-1.pdf";

    /**
     * URL to the traditional promises book hosted within WP.
     */
    public static string $TRADITIONAL_PROMISES_BOOK_URL = "https://staffordshireceremonies.co.uk/wp-content/uploads/Marriage-Traditional-Promises.pdf";

    /**
     * URL to the traditional readings book hosted within WP.
     */
    public static string $TRADITIONAL_READINGS_BOOK_URL = "https://staffordshireceremonies.co.uk/wp-content/uploads/Marriage-Traditional-Readings.pdf";

    /**
     * URL to the enhanced books hosted within WP.
     */
    public static string $ENHANCED_READINGS_BOOK_URL = "https://staffordshireceremonies.co.uk/wp-content/uploads/Marriage-Enhanced-Readings.pdf";
    public static string $ENHANCED_PROMISES_BOOK_URL = "https://staffordshireceremonies.co.uk/wp-content/uploads/Marriage-Enhanced-Promises.pdf";

    public array $form = [];
    public array $fields = [];

    public static function create()
    {
        return new self();
    }

    public function __construct()
    {
        return $this;
    }

    public function output()
    {
        return json_encode($this->form);
    }

    private $currentSection = '';

    public function getFieldsFlat()
    {
        $fields = [];

        /**
         * 66 fields appearing, was 81. So 15 fields are not appearing.
         */
        foreach($this->form as $key => $section) {
            $this->currentSection = $section['name'];
            if (isset($section['fields'])) {
                $flat = $this->flattenFields($section['fields']) ?? [];
                $fields = array_merge($fields, $flat);
            } else if (isset($section['sections'])) {
                foreach ($section['sections'] as $subSection) {
                    $flat = $this->flattenFields($subSection['fields']) ?? [];
                    $fields = array_merge($fields, $flat);
                }
            }
        }

        foreach ($fields as $key => $field) {
            if (isset($field['name'])) {
                unset($fields[$key]);
                $fields[$field['name']] = $field;
            }
        }

        return $fields;

    }

    private function flattenFields($fields)
    {
        $flattenedFields = [];

        foreach ($fields as $field) {

            if (in_array($field['component'], ['Title', 'Subtitle', 'Content'])) {
                continue;
            }

            if (isset($field['fields']) && is_array($field['fields'])) {
                $flattenedFields = array_merge($flattenedFields, $this->flattenFields($field['fields']));
            } else if (in_array($field['component'], ['FormRadioConditional', 'FormSelectConditional'])) {
                foreach ($field['options'] as $option) {
                    if (isset($option['fields'])) {
                        $flattenedFields = array_merge($flattenedFields, $this->flattenFields($option['fields']));
                    }
                }
                $field['section'] = $this->currentSection;
                $flattenedFields[] = $field;
            } else {
                $field['section'] = $this->currentSection;
                $flattenedFields[] = $field;
            }
        }

        return $flattenedFields;
    }

    public function addFields($fields)
    {
        $this->form = $fields;
    }

    public function addSection($name, $content, $fields)
    {
        return [
            "name" => $name,
            "content" => $content,
            "fields" => $this->addSectionKeys($fields, $name),
//            "fields" => $fields
        ];
    }

    private function addSectionKeys($fields, $name)
    {
        foreach ($fields as $key => $field) {
            if (isset($field['fields'])) {
                $fields[$key]['fields'] = $this->addSectionKeys($field, $name);
            } else {
                $fields[$key]['section'] = $name;
            }
        }

        return $fields;
    }

    public function addTitle($title)
    {
        return [
            "key" => md5($title),
            "component" => "Title",
            "content" => $title,
        ];
    }

    public function addSubtitle($subtitle)
    {
        return [
            "key" => md5($subtitle),
            "component" => "Subtitle",
            "content" => $subtitle,
        ];
    }

    public function addContent($content)
    {
        return [
            "key" => md5($content),
            "component" => "Content",
            "content" => $content,
        ];
    }

    public function addText($label, $name, $hint = "", $required = true)
    {
        $field = [
            "key" => md5($label . $name . $hint),
            "component" => "FormText",
            "label" => $label,
            "name" => $name,
            "hint" => $hint,
            "required" => $required,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addTextarea($label, $name, $hint = "", $required = true)
    {
        $field = [
            "component" => "FormTextarea",
            "label" => $label,
            "name" => $name,
            "hint" => $hint,
            "required" => $required,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addCheckbox($label, $name, $options)
    {
        $field = [
            "component" => "FormCheckboxes",
            "label" => $label,
            "name" => $name,
            "options" => $options,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addRadio($label, $name, $options)
    {
        $field = [
            "component" => "FormRadios",
            "label" => $label,
            "name" => $name,
            "options" => $options,
            "required" => true,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addRadioOption($label, $value)
    {
        return ["label" => $label, "value" => $value];
    }

    public function addRadioConditionalOption($label, $value, $fields)
    {
        return ["label" => $label, "value" => $value, "fields" => $fields];
    }

    public function addSelect($label, $name, $options)
    {
        $field = [
            "key" => md5($label . $name),
            "component" => "FormSelect",
            "label" => $label,
            "name" => $name,
            "required" => true,
            "options" => $options,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addSelectConditional($label, $name, $options)
    {
        $field = [
            "component" => "FormSelectConditional",
            "label" => $label,
            "name" => $name,
            "options" => $options,
            "required" => true,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addSelectConditionalOption($label, $value, $fields)
    {
        return ["label" => $label, "value" => $value, "fields" => $fields];
    }

    public function addRadioConditional($label, $name, $options)
    {
        $field = [
            "component" => "FormRadioConditional",
            "label" => $label,
            "name" => $name,
            "options" => $options,
            "required" => true,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addConditionalSection($value, $fields)
    {
        return [
            "condition_value" => $value,
            "fields" => $fields,
        ];
    }

    /**
     * Inherits title from $this->addConditionalSection()
     *
     * @param $name
     * @param $sections
     * @return array
     */
    public function addConditionalSections(
        $name,
        $content,
        $valueName,
        $sections
    ) {
        return [
            "name" => $name,
            "content" => $content,
            "conditional_value_name" => $valueName,
            "sections" => $sections,
        ];
    }

    public function addFile($label, $name)
    {
        $field = [
            "component" => "FormFile",
            "label" => $label,
            "name" => $name,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addSpecialCheck($label, $name)
    {
        $field = [
            "component" => "FormComplete",
            "label" => $label,
            "name" => $name,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addDate($label, $name)
    {
        $field = [
            "component" => "FormDate",
            "label" => $label,
            "name" => $name,
        ];
        return $field;
    }

    public function addTime($label, $name)
    {
        $field = [
            "component" => "FormTime",
            "label" => $label,
            "name" => $name,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addCeremonyBookReference(
        $label = "See our ceremony book for more information",
        $url = ""
    ) {
        return [
            "component" => "FormCeremonyBook",
            "label" => $label,
            "url" => $url ?? self::$STD_BOOK_URL,
        ];
    }

    public function addDeclarations($label, $name, $declarations)
    {
        $field = [
            "component" => "FormDeclarations",
            "label" => $label,
            "name" => $name,
            "options" => $declarations,
            "required" => true,
        ];
        $this->fields[] = $field;
        return $field;
    }

    public function addDeclarationOption($value, $declaratory, $contracting)
    {
        return [
            "declaratory" => $declaratory,
            "contracting" => $contracting,
            "value" => $value,
        ];
    }

    public function getPromisesList()
    {
        return [
            "Promise 1",
            "Promise 2",
            "Promise 3",
            "Promise 4",
            "Promise 5",
            "Promise 6",
            "Promise 7",
            "Promise 8",
        ];
    }

    public function getReadingsList()
    {
        return [
            "Always Love Each Other",
            "Take Time",
            "From an American Indian Ceremony",
            "When You Marry Her",
            "Les Miserables",
            'Captain Corelli\'s Mandolin',
            "What is Love",
            "This Day",
            "The Art of Marriage",
            "Wedding Day (By Rowena Edlin-White)",
            "Wedding Day (By Robert Palmer)",
            "Your Wedding Day",
            "Marriage Joins Two People in the Circle of its Love",
        ];
    }

    public function getRingPromises()
    {
        return [
            "Ring Promise 1",
            "Ring Promise 2",
            "Ring Promise 3",
            "Ring Promise 4",
        ];
    }

    public function getHandReadings(): array
    {
        return [
            "Hand Reading 1",
            "Hand Reading 2",
            "Hand Reading 3",
            "Hand Reading 4",
        ];
    }

    /**
     * An array of all current SCC approved venues.
     * TODO: This ought to be content managed at some point.
     *
     * @return string[]
     */
    public function getVenueList(): array
    {
        return [
            "Alrewas Hayes",
            "Ancient High House",
            "Ashcombe Park Estate",
            "Aston Marina",
            "Aston Wood Golf Club",
            "Barlaston Hall",
            "Best Western The George Hotel",
            "Bilston Brook Wedding Barn",
            "Blakelands Country House",
            "Blithfield Lakeside Barns",
            "Borough Arms Hotel",
            "Branston Golf & Country Club",
            "Brocton Hall Golf Club",
            "Buddileigh Farm",
            "Burton Albion Football Club",
            "Churnet Valley Railway - Cheddleton Station",
            "Churnet Valley Railway - Consall Station",
            "Coton House Farm",
            "County Buildings",
            "Dilhorne Recreation Centre",
            "Dorothy Clive Garden",
            "Dovecliff Hall Hotel",
            "Dovedale Barn",
            "Lower Damgate Farm",
            "Drayton Manor Theme Park",
            "Dunsley Hall Hotel",
            "Dunwood Hall",
            "Erasmus Darwin House",
            "Fox Inn",
            "Foxtail Barns, Consall Hall",
            'Georgie\'s Canal Cruises (The Georgie Kate)',
            "Gradbach Mill",
            "Granvilles Restaurant",
            "Guildhall",
            "Haling Dene Centre",
            "Hanbury Wedding Barn",
            "Harleys of Kinver",
            "Hawkesyard Estate",
            "Heaton House Farm",
            "Himley Hall & Park",
            "Hoar Cross Hall Spa Hotel",
            "Holiday Inn Birmingham North-Cannock",
            "Horton Village Hall",
            "Hotel Rudyard",
            "Ingestre Hall",
            'Izaak Walton\'s Cottage',
            "Keele Hall",
            "Leek Weddings",
            "Littywood Manor",
            "Mayfield Hall",
            "Moddershall Oaks",
            "Moorville Hall Hotel",
            "Oak Farm Hotel",
            "Park Hall Farm",
            "Pendrell Hall Exclusive Country House",
            "Registration Office Burton on Trent",
            "Registration Office Cannock",
            "Registration Office Leek",
            "Registration Office Lichfield",
            "Registration Office Newcastle under Lyme",
            "Registration Office Stafford",
            "Riverside Hotel",
            "Roman Way Hotel",
            "Rugeley Rose Theatre & Community Hall",
            "Sandon Hall",
            "Slaters Country Hotel & Inn",
            "Somerford Hall",
            "St Johns House",
            "Standon Hall",
            "Statfold Roundhouse",
            "Stone House Hotel",
            "Swinfen Hall Hotel",
            "Tamworth Castle",
            "The Dove Room Leek Reg Office",
            "The Aquarius Ballroom",
            "The Ashes Barns",
            "The Barns at Hanbury",
            "The Castle Hotel",
            "The Chase Golf Club",
            "The Courtyard @ No. 12",
            "The Cowshed-Woodhall Farm",
            "The Crooked House",
            "The Drawing Room",
            "The Izaak Walton Hotel",
            "The Manor at Old Hadley",
            "The Mill at Worston",
            "The Mill Restaurant & Hotel",
            "The Moat House",
            "The Oaks Barn",
            "The Old School House Restaurant",
            "The Orangery",
            "The Post House Bar & Grill",
            "The Raddle Inn",
            "The Trentham Estate",
            "The Trinity",
            "The Upper House",
            'The Waterfront Crow\'s Nest',
            "The Winery",
            "Thornbury Hall",
            "Thorpe Garden",
            "Three Horseshoes Inn",
            "Town Hall Burton",
            "Town Hall Uttoxeter",
            "Tutbury Castle",
            "Uttoxeter Racecourse",
            "Weston Hall",
            "Weston Park",
            "Wheatsheaf Inn",
            "Whiston Hall",
        ];
    }
}
