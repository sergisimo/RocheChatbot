<?php

namespace APIRoche;

use PDO;

class DAOStudy {

    /* ************* CONSTANTS ****************/
    const PGSQL_HOST = 'aact-prod.cr4nrslb1lw7.us-east-1.rds.amazonaws.com';
    const DATABASE_NAME = 'aact';
    const USER_NAME = 'aact';
    const USER_PSWD = 'aact';

    // FORM SEARCH ID's STATEMENTS
    const SELECT_ID_FROM_KEYWORDS = 'SELECT DISTINCT keywords.nct_id FROM keywords WHERE ';
    const SELECT_ID_FROM_NO_RESULTS = 'SELECT DISTINCT studies.nct_id FROM studies WHERE studies.nct_id NOT IN (SELECT DISTINCT result_groups.nct_id FROM result_groups)';
    const SELECT_ID_FROM_RESULTS = 'SELECT DISTINCT result_groups.nct_id FROM result_groups';
    const SELECT_ID_FROM_COUNTRIES = 'SELECT DISTINCT countries.nct_id FROM countries WHERE ';
    const SELECT_ID_FROM_CITIES = 'SELECT DISTINCT facilities.nct_id FROM facilities WHERE ';
    const SELECT_ID_FROM_CONDITIONS = 'SELECT DISTINCT conditions.nct_id FROM conditions WHERE ';
    const SELECT_ID_FROM_PHASE = 'SELECT DISTINCT studies.nct_id FROM studies WHERE ';
    const SELECT_ID_FROM_STATUS = 'SELECT DISTINCT studies.nct_id FROM studies WHERE ';
    const SELECT_ID_FROM_ELIGIBILITIES = 'SELECT DISTINCT eligibilities.nct_id FROM eligibilities WHERE eligibilities.gender = :gender';
    const SELECT_ID_FROM_TYPE = 'SELECT DISTINCT studies.nct_id FROM studies WHERE ';

    // FORM GET INFORMATION STATEMENTS
    const SELECT_INFO_FROM_STUDIES = 'SELECT brief_title, overall_status FROM studies WHERE studies.nct_id = :id';
    const SELECT_INFO_FROM_CONDITIONS = 'SELECT name FROM conditions WHERE conditions.nct_id = :id';
    const SELECT_INFO_FROM_FACILITES = 'SELECT city, state, country FROM facilities WHERE facilities.nct_id = :id';
    const SELECT_INFO_FROM_INTERVENTION = 'SELECT name FROM interventions WHERE interventions.nct_id = :id';

    //BOT STATEMENTS
    const SELECT_DISEASE_STATUS_PERIOD_COUNTRY = 'SELECT studies.start_date FROM studies, countries, conditions WHERE studies.nct_id = countries.nct_id AND studies.nct_id = conditions.nct_id AND countries.name LIKE :country AND conditions.name LIKE :condition';

    /* ************* ATTRIBUTES ****************/
    private static $instance;
    private $dbConnection;
    private $searchIDFormStatement;
    private $searchFormInfoStatement;
    private $botStatement;

    /* ************* CONSTRUCTOR ****************/
    private function __construct() {

        $this->dbConnection = new PDO('pgsql:host=' . DAOStudy::PGSQL_HOST . ';dbname=' . DAOStudy::DATABASE_NAME, DAOStudy::USER_NAME, DAOStudy::USER_PSWD);
    }

    public static function getInstance(): DAOStudy {

        if (DAOStudy::$instance == null) DAOStudy::$instance = new DAOStudy();

        return DAOStudy::$instance;
    }

    /* ************* PUBLIC METHODS ****************/
    public function formSearch($keywords, $studyType, $studyResults, $studyStatus, $sex, $countries, $cities, $conditions, $phases) {

        $idMatrix = array();
        $count = 0;

        //KEYWORDS
        if (count($keywords) != 0 && $keywords != null) {
            $idMatrix[$count] = $this->getIDFromKeywords($keywords);
            $count++;
        }

        //TYPE
        if (count($studyType) != 0 && $studyType != null) {
            $idMatrix[$count] = $this->getIDFromType($studyType);
            $count++;
        }

        //RESULTS
        if ($studyResults != 'none' && $studyResults != null) {
            $idMatrix[$count] = $this->getIDFromResults($studyResults);
            $count++;
        }

        //STATUS
        if (count($studyStatus) != 0 && $studyStatus != null) {
            $idMatrix[$count] = $this->getIDFromStatus($studyStatus);
            $count++;
        }

        //SEX
        if ($sex != 'Both' && $sex != null) {
            $idMatrix[$count] = $this->getIDFromSex($sex);
            $count++;
        }

        //COUNTRIES
        if (count($countries) != 0 && $countries != null) {
            $idMatrix[$count] = $this->getIDFromCountries($countries);
            $count++;
        }

        //CITIES
        if (count($cities) != 0 && $cities != null) {
            $idMatrix[$count] = $this->getIDFromCities($cities);
            $count++;
        }

        //CONDITIONS
        if (count($conditions) != 0 && $conditions != null) {
            $idMatrix[$count] = $this->getIDFromConditions($conditions);
            $count++;
        }

        //PHASES
        if (count($phases) != 0 && $phases != null) $idMatrix[$count] = $this->getIDFromPhases($phases);

        //return $idMatrix;
        // GET INFO FROM SELECTED ID's
        $identifiers = $this->intersectIDArrays($idMatrix);

        return $this->getResultsInfo($identifiers);
    }

    public function diseaseStatusPeriodCountry($country, $condition) {

        $this->botStatement = $this->dbConnection->prepare(DAOStudy::SELECT_DISEASE_STATUS_PERIOD_COUNTRY);
        $country = '%' . $country . '%';
        $condition = '%' . $condition . '%';
        $this->botStatement->bindParam(':country', $country, PDO::PARAM_STR);
        $this->botStatement->bindParam(':condition', $condition, PDO::PARAM_STR);
        $this->botStatement->execute();
        $results = $this->botStatement->fetchAll();

        $values = array();
        foreach ($results as $result) {

            $year = idate('Y', strtotime($result['start_date']));
            if (array_key_exists($year, $values)) $values[$year]++;
            else $values[$year] = 1;
        }

        return $values;
    }

    /* ************* PRIVATE METHODS ****************/
    private function getResultsInfo($identifiers) {

        $results = array();
        $count = 0;
        foreach ($identifiers as $identifier) {

            $study = array();

            $this->searchFormInfoStatement = $this->dbConnection->prepare(DAOStudy::SELECT_INFO_FROM_STUDIES);
            $this->searchFormInfoStatement->bindParam(':id', $identifier, PDO::PARAM_STR);
            $this->searchFormInfoStatement->execute();
            $info = $this->searchFormInfoStatement->fetch();

            $study['title'] = $info['brief_title'];
            $study['status'] = $info['overall_status'];

            $this->searchFormInfoStatement = $this->dbConnection->prepare(DAOStudy::SELECT_INFO_FROM_CONDITIONS);
            $this->searchFormInfoStatement->bindParam(':id', $identifier, PDO::PARAM_STR);
            $this->searchFormInfoStatement->execute();
            $infos = $this->searchFormInfoStatement->fetchAll();
            $conditions = array();
            foreach ($infos as $info) array_push($conditions, $info['name']);
            $study['conditions'] = $conditions;

            $this->searchFormInfoStatement = $this->dbConnection->prepare(DAOStudy::SELECT_INFO_FROM_INTERVENTION);
            $this->searchFormInfoStatement->bindParam(':id', $identifier, PDO::PARAM_STR);
            $this->searchFormInfoStatement->execute();
            $infos = $this->searchFormInfoStatement->fetchAll();
            $interventions = array();
            foreach ($infos as $info) array_push($interventions, $info['name']);
            $study['interventions'] = $interventions;

            $this->searchFormInfoStatement = $this->dbConnection->prepare(DAOStudy::SELECT_INFO_FROM_FACILITES);
            $this->searchFormInfoStatement->bindParam(':id', $identifier, PDO::PARAM_STR);
            $this->searchFormInfoStatement->execute();
            $infos = $this->searchFormInfoStatement->fetchAll();
            $locations = array();
            foreach ($infos as $info) array_push($locations, $info['city'] . ', ' . $info['state'] . ', ' . $info['country']);
            $study['locations'] = $locations;

            array_push($results, $study);

            $count++;
            if ($count == 5) break;
        }

        return $results;
    }

    private function getIDFromKeywords($keywords) {

        $query = DAOStudy::SELECT_ID_FROM_KEYWORDS;
        foreach ($keywords as $keyword) {

            $query = $query . 'keywords.name LIKE \'%' . $keyword  . '%\'';
            if ($keyword != $keywords[count($keywords) - 1]) $query = $query . ' OR ';
        }
        $this->searchIDFormStatement = $this->dbConnection->prepare($query);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $keywordsID = array();
        foreach ($identifiers as $identifier) array_push($keywordsID, $identifier['nct_id']);

        return $keywordsID;
    }

    private function getIDFromType($studyTypes) {

        $query = DAOStudy::SELECT_ID_FROM_TYPE;
        foreach ($studyTypes as $type) {

            $query = $query . 'studies.study_type LIKE \'%' . $type . '%\'';
            if ($type != $studyTypes[count($studyTypes) - 1]) $query = $query . ' OR ';
        }

        $this->searchIDFormStatement = $this->dbConnection->prepare($query);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $typeID = array();
        foreach ($identifiers as $identifier) array_push($typeID, $identifier['nct_id']);

        return $typeID;
    }

    private function getIDFromResults($studyResults) {

        if ($studyResults == 'true') $query = DAOStudy::SELECT_ID_FROM_RESULTS;
        else $query = DAOStudy::SELECT_ID_FROM_NO_RESULTS;
        $this->searchIDFormStatement = $this->dbConnection->prepare($query);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $resultsID = array();
        foreach ($identifiers as $identifier) array_push($resultsID, $identifier['nct_id']);

        return $resultsID;
    }

    private function getIDFromStatus($studyStatus) {

        $query = DAOStudy::SELECT_ID_FROM_STATUS;
        foreach ($studyStatus as $status) {

            $query = $query . 'studies.overall_status LIKE \'%' . $status . '%\'';
            if ($status != $studyStatus[count($studyStatus) - 1]) $query = $query . ' OR ';
        }

        $this->searchIDFormStatement = $this->dbConnection->prepare($query);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $statusID = array();
        foreach ($identifiers as $identifier) array_push($statusID, $identifier['nct_id']);

        return $statusID;
    }

    private function getIDFromSex($sex) {

        $this->searchIDFormStatement = $this->dbConnection->prepare(DAOStudy::SELECT_ID_FROM_ELIGIBILITIES);
        $this->searchIDFormStatement->bindParam(':gender', $sex, PDO::PARAM_STR);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $sexID = array();
        foreach ($identifiers as $identifier) array_push($sexID, $identifier['nct_id']);

        return $sexID;
    }

    private function getIDFromCountries($countries) {

        $query = DAOStudy::SELECT_ID_FROM_COUNTRIES;
        foreach ($countries as $country) {

            $query = $query . 'countries.name LIKE \'%' . $country . '%\'';
            if ($country != $countries[count($countries) - 1]) $query = $query . ' OR ';
        }

        $this->searchIDFormStatement = $this->dbConnection->prepare($query);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $countriesID = array();
        foreach ($identifiers as $identifier) array_push($countriesID, $identifier['nct_id']);

        return $countriesID;
    }

    private function getIDFromCities($cities) {

        $query = DAOStudy::SELECT_ID_FROM_CITIES;
        foreach ($cities as $city) {

            $query = $query . 'facilities.city LIKE \'%' . $city . '%\' OR facilities.state LIKE \'%' . $city . '%\'';
            if ($city != $cities[count($cities) - 1]) $query = $query . ' OR ';
        }

        $this->searchIDFormStatement = $this->dbConnection->prepare($query);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $citiesID = array();
        foreach ($identifiers as $identifier) array_push($citiesID, $identifier['nct_id']);

        return $citiesID;
    }

    private function getIDFromConditions($conditions) {

        $query = DAOStudy::SELECT_ID_FROM_CONDITIONS;
        foreach ($conditions as $condition) {

            $query = $query . 'conditions.name LIKE \'%' . $condition . '%\'';
            if ($condition != $conditions[count($conditions) - 1]) $query = $query . ' OR ';
        }

        $this->searchIDFormStatement = $this->dbConnection->prepare($query);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $conditionsID = array();
        foreach ($identifiers as $identifier) array_push($conditionsID, $identifier['nct_id']);

        return $conditionsID;
    }

    private function getIDFromPhases($phases) {

        $query = DAOStudy::SELECT_ID_FROM_PHASE;
        foreach ($phases as $phase) {

            $query = $query . 'studies.phase LIKE \'%' . $phase . '%\'';
            if ($phase != $phases[count($phases) - 1]) $query = $query . ' OR ';
        }

        $this->searchIDFormStatement = $this->dbConnection->prepare($query);
        $this->searchIDFormStatement->execute();
        $identifiers = $this->searchIDFormStatement->fetchAll();
        $phasesID = array();
        foreach ($identifiers as $identifier) array_push($phasesID, $identifier['nct_id']);

        return $phasesID;
    }

    private function intersectIDArrays($idMatrix) {

        if (count($idMatrix) == 1) return $idMatrix[0];

        $identifiers = array_intersect($idMatrix[0], $idMatrix[1]);

        for ($i = 2; $i < count($idMatrix); $i++) {

            $identifiers = array_intersect($identifiers, $idMatrix[$i]);
        }

        return $identifiers;
    }
}
