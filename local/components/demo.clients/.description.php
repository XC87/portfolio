<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
    "NAME" => GetMessage("SODAL_NAME"),
    "DESCRIPTION" => GetMessage("SODAL_DESCR"),
    "PATH" => array(
        "ID" => "content",
        "CHILD" => array(
            "ID" => "clients",
            "NAME" => GetMessage("SODAL_NAME"),
            "CHILD" => array(
                "ID" => "clients_dtlx",
            ),
        )
    ),
);