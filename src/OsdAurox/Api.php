<?php

namespace OsdAurox;

class Api
{
    public bool $status = false;
    public array $infos = [];
    public array $errors = [];
    public array $success = [];
    public array $warnings = [];
    public array $datas = [];
    public array $validators = [];
    public string $redirect_url = '';
    public string $html = '';
    public string $json = '';

    public function __construct()
    {
        $this->status = false;
        $this->infos = [];
        $this->errors = [];
        $this->success = [];
        $this->warnings = [];
        $this->datas = [];
        $this->html = '';
        $this->validators = [];
    }

    /**
     * Validates and formats a field with the provided message and type.
     *
     * @param string $field The name of the field to validate.
     * @param string $msg The message to associate with the field.
     * @param string $type The type of the message, default is 'danger'.
     * @return array An associative array containing the field, message, and type.
     */
    private function validatorfield(string $field, string $msg, string $type='danger'): array
    {
        return [
            'field' => $field,
            'msg' => $msg,
            'type' => $type
        ];
    }

    public function addValidatorField(string $field, string $msg, string $type='danger'): void
    {
        $this->validators[] = $this->validatorfield($field, $msg, $type);
    }

    public function returnJsonResponse($pretty = false, $output=false)
    {
        if (!$output) {
            header('Content-Type: application/json');
        }
        $flat_array = [
            'status' => $this->status,
            'infos' => $this->infos,
            'errors' => $this->errors,
            'success' => $this->success,
            'warnings' => $this->warnings,
            'datas' => $this->datas,
            'validators' => $this->validators,
            'redirect_url' => $this->redirect_url,
        ];
        if($this->html) {
            $flat_array['html'] = $this->html;
        }
        $this->json = json_encode($flat_array, $pretty ? JSON_PRETTY_PRINT : 0);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $res = json_encode([
                'status' => false,
                'errors' => ['JSON encoding error'],
            ]);
            if ($output) {
                return $res;
            }
            echo $res;
            Base::dieOrThrow();
            return;
        }

        if($output) {
            return $this->json;
        }
        echo $this->json;
        Base::dieOrThrow();
    }
}