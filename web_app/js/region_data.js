//
//	Copyright (c) 2014-2015, Emory University
//	All rights reserved.
//
//	Redistribution and use in source and binary forms, with or without modification, are
//	permitted provided that the following conditions are met:
//
//	1. Redistributions of source code must retain the above copyright notice, this list of
//	conditions and the following disclaimer.
//
//	2. Redistributions in binary form must reproduce the above copyright notice, this list
// 	of conditions and the following disclaimer in the documentation and/or other materials
//	provided with the distribution.
//
//	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
//	EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
//	OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
//	SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
//	INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
//	TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
//	BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
//	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
//	WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
//	DAMAGE.
//
//
var uid = "";
var _submit = document.getElementById('_submit');
var _progress = document.getElementById('_progress');
var featurename = "";

$(function() {

	var	boundarySel = $("#boundarySel"),
		pyramidSel = $("#pyramidSel"), slideSel = $("#slideSel"),
		slideInfo = $('#slideInfo'), deleteDatasetSel = $("#deleteDatasetSel");

		// get session vars
		//
		$.ajax({
			url: "php/getSession.php",
			data: "",
			dataType: "json",
			success: function(data) {

				uid = data['uid'];
				classifier = data['className'];
				posClass = data['posClass'];
				negClass = data['negClass'];
				curDataset = data['dataset'];
				IIPServer = data['IIPServer'];

				if( uid === null ) {
					$('#nav_select').hide();
					$('#nav_heatmaps').hide();
					$('#nav_review').hide();
				} else {
					// No reports while session active
					$('#nav_reports').hide();

					// TODO - Populate the text fields with the session values.
					// This way we can see the criteria for the
					// current session
				}
			}
		});

		$('#progDiag').modal('hide');
		$('#_form').submit(function() {
    		$('#progDiag').modal('show');
		});

	$.ajax({
		type: "POST",
		url: "php/region_getdatasetsfromdir.php",
		data: "",
		dataType: "json",
		success: function(data) {
			// set first featurename
			for (var i = 0; i < data['dirNames'].length; i++) {
				$("#datasetSel").append(new Option(data['dirNames'][i], data['dirNames'][i]));
			}
			updateFeatures();
		}
	});

	$.ajax({
		type: "POST",
		url: "php/region_getboundariesfromdir.php",
		data: "",
		dataType: "json",
		success: function(data) {

			for (var i = 0; i < data['dirNames'].length; i++) {
				boundarySel.append(new Option(data['dirNames'][i], data['dirNames'][i]));
			}
			//updateSlideList();
		}
	});

	$.ajax({
		type: "POST",
		url: "php/region_getpyramidinfofromdir.php",
		data: "",
		dataType: "json",
		success: function(data) {

			for (var i = 0; i < data['dirNames'].length; i++) {
				pyramidSel.append(new Option(data['dirNames'][i], data['dirNames'][i]));
			}
			//updateSlideList();
		}
	});

	$.ajax({
		type: "POST",
		url: "php/region_getslideinfofromdir.php",
		data: "",
		dataType: "json",
		success: function(data) {

			for (var i = 0; i < data['dirNames'].length; i++) {
				slideSel.append(new Option(data['dirNames'][i], data['dirNames'][i]));
			}
			//updateSlideList();
		}
	});
	// Need to montior changes for the map score select controls. Slide image
	//	size is dependant on these.
	//
	//$("#datasetSel").change(updateDataset);
	//$("#boundarySel").change(updateBoundaries);
	$('input[type="radio"]').on('change', function(){
    if($('#slideInfo').is(':checked')) {
        $('#pyramidSel').show();
    } else {
        $('#pyramidSel').hide();
    }
	})


	$.ajax({
		url: "db/region_getdatasets.php",
		data: "",
		dataType: "json",
		success: function(data) {

			for( var item in data ) {
				deleteDatasetSel.append(new Option(data[item][0], data[item][0]));
			}
		}
	});

	$("#datasetSel").change(updateFeatures);

});


//
//	Updates the list of available slides for the current dataset
//
function updateFeatures() {

	featurename = datasetSel.options[datasetSel.selectedIndex].label;
	document.getElementById('featureName').innerHTML = featurename;

	// Get the information for the current dataset
	$.ajax({
		type: "POST",
		url: "php/region_getfeatures.php",
		data: { featurename: featurename },
		dataType: "json",
		success: function(data) {

      document.getElementById('featureInfo').innerHTML = data['features'];

		}
	});
}
