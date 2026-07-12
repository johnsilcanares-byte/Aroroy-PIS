<?php 

function getGender($gender = '') {
	$data = '<option value="">Select Gender</option>';
	$arr = array("Male", "Female", "Other");

	foreach($arr as $g) {
		$selected = ($gender == $g) ? 'selected' : '';
		$data .= '<option value="'.$g.'" '.$selected.'>'.$g.'</option>';
	}
	return $data;
}

function getMedicines($con, $medicineId = 0) {
	$query = "SELECT id, medicine_name FROM medicines ORDER BY medicine_name ASC";
	$stmt = $con->prepare($query);

	try {
		$stmt->execute();
	} catch(PDOException $ex) {
		die($ex->getMessage());
	}

	$data = '<option value="">Select Medicine</option>';

	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$selected = ($medicineId == $row['id']) ? 'selected' : '';
		$data .= '<option value="'.$row['id'].'" '.$selected.'>'.$row['medicine_name'].'</option>';
	}

	return $data;
}

function getPatients($con) {
	$query = "SELECT id, patient_name, phone_number FROM patients ORDER BY patient_name ASC";
	$stmt = $con->prepare($query);

	try {
		$stmt->execute();
	} catch(PDOException $ex) {
		die($ex->getMessage());
	}

	$data = '<option value="">Select Patient</option>';

	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$data .= '<option value="'.$row['id'].'">'.$row['patient_name'].' ('.$row['phone_number'].')</option>';
	}

	return $data;
}

function getDateTextBox($label, $dateId) {

	$d = '<div class="col-lg-3 col-md-3 col-sm-4 col-xs-10">
            <div class="form-group">
              <label>'.$label.'</label>

              <div class="input-group rounded-0 date" 
                   id="'.$dateId.'" 
                   data-target-input="nearest">

                <input type="text" 
                       class="form-control form-control-sm rounded-0 datetimepicker-input" 
                       data-target="#'.$dateId.'" 
                       name="'.$dateId.'" 
                       id="'.$dateId.'_input"
                       autocomplete="off"/>

                <div class="input-group-append rounded-0" 
                     data-target="#'.$dateId.'" 
                     data-toggle="datetimepicker">
                  <div class="input-group-text">
                    <i class="fa fa-calendar"></i>
                  </div>
                </div>

              </div>
            </div>
          </div>';

	return $d;
}