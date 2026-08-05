<?php

namespace app\Services;

use app\Models\DocumentType;

class DocumentTypeService
{
    public function getDocumentTypes()
    {
        return DocumentType::all();
    }

    public function getDocumentType($id)
    {
        return DocumentType::find($id);
    }

    public function getByName(string $name)
    {
        return DocumentType::where("name", $name)->first();
    }

    public function save(string $name)
    {
        return DocumentType::firstOrCreate(["name" => $name]);
    }

    public function update(string $name, DocumentType $type)
    {
        $type->name = $name;
        $type->save();

        return $type;
    }

    public function delete(DocumentType $documentType)
    {
        $documentType->delete();
    }
}