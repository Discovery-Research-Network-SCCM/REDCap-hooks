var PullService = {

    group_id: null,
    project_id: null,
    event_id: null,
    instrument: null,
    hostName: null,
    encounter_start: null,
    encounter_end: null,
    mrn_txt: null,
    request_timeout: 0,

    /*
        Parametrized URLs
    */

    //define Url for request Patient information
    getPatientUrl: function(mrn, group_id){
        //var result = "http://" + this.hostName + ":8282/usciitg-prep-ws/api/fhir/patient/" + encodeURIComponent(mrn) + "?";
        var result = location.protocol + "//" + this.hostName + "/redcap/hooks/global/PullServiceGetPatient.php?";

        //All parameteres have to be omited if empty
        if (mrn){
            result += "&mrn=" + encodeURIComponent(mrn);
        }
        if (group_id){
            result += "&group_id=" + encodeURIComponent(group_id);
        }
        return result;
    },

    //define Url for request ODM XML information
    getODMUrl: function(project_id, event_id, instrument, group_id, mrn, encounter_start, encounter_end){
        //var result = location.protocol + "//"+this.hostName+":8282/usciitg-prep-ws/api/fhir/patient/" + encodeURIComponent(mrn) + "/odm?";
        var result = location.protocol + "//" + this.hostName + "/redcap/hooks/global/PullServiceGetODM.php?";

        //All parameteres have to be omited if empty
        if (mrn){
            result += "&mrn=" + encodeURIComponent(mrn);
        }
        if (group_id){
            result += "&group_id=" + encodeURIComponent(group_id);
        }
        if (project_id){
            result += "&project_id=" + encodeURIComponent(project_id);
        }
        if (event_id){
            result += "&event_id=" + encodeURIComponent(event_id);
        }
        if (instrument){
            result += "&instrument=" + encodeURIComponent(instrument);
        }
        if (encounter_start){
            result += "&encounter_start=" + encodeURIComponent(new Date(encounter_start).toISOString());
        }
        if (encounter_end){
            result += "&encounter_end=" + encodeURIComponent(new Date(encounter_end).toISOString());
        }
        return result;
    },
    /*
        Methods
    */
    //Will hide all existed method notifications on dialog
    hideAllErrors: function(){
        $(".redInfo").hide();
    },
    //Clear all text fields from dialog
    clearMainFormFields: function() {
        $(".mainFormField").val("");
    },
    //Show/Hide main form dialog
    showMainForm: function(isVisible){
        if (isVisible){
            $("#mainForm").show();
        }else {
            $("#mainForm").hide();
        }
    },
    //Show/Hide patient form dialog
    showPatientForm: function(isVisible){
        if (isVisible){
            $("#patientForm").show();
        }else {
            $("#patientForm").hide();
        }
    },
    //Show/Hide data request dialog
    showImportRequestRow: function(isVisible){
        if (isVisible){
            $("#importDataRow").show();
            $("#correctPatient").hide();
        }else {
            $("#importDataRow").hide();
            $("#correctPatient").show();
        }
    },
    //Show/Hide message that form has data which will be erased or replaced
    showDirtyMessage: function(isVisible){
        if (isVisible){
            $("#dirtyData").show();
        }else {
            $("#dirtyData").hide();
        }
    },
    //Show MRN required error
    showMRNRequired: function(isVisible){
        if (isVisible){
            $("#mrnRequired").show();
        }else {
            $("#mrnRequired").hide();
        }
    },
    //Show Encounter Start required error
    showEncounterStartRequired: function(isVisible){
        if (isVisible){
            $("#EncounterStartRequired").show();
        }else {
            $("#EncounterStartRequired").hide();
        }
    },
    //Show pull data dialog
    showPullDataDialog: function(){
        this.hideAllErrors(); //Clear all errors from previous runs.. just in case
        this.clearMainFormFields(); //
        this.showMainForm(true);
        this.showPatientForm(false);
        this.showImportRequestRow(false);
        this.showInfoMsg("");
        this.setCloseBtn(false);
        $("#MRNDilaog").dialog({Width: 600, minWidth: 450, modal : true});
        this.showDirtyMessage(this.checkDirtyData());
    },
    //Check if any fields are populated
    checkDirtyData: function(){
        var el = $("input[type='text']");
        for(var k = 0; k < el.length; k++){
            var item = $(el[k]);
            if (item.val()){
                return true;
            }
        }

        el = $("input[type='radio']");
        for(var k = 0; k < el.length; k++){
            var item = $(el[k]);
            if (item.prop("checked")){
                return true;
            }
        }

        el = $("select");
        for(var k = 0; k < el.length; k++){
            var item = $(el[k]);
            if (item.prop("name") == "sprint_sari_rapid_crf_complete"){
                continue;
            }
            if (item.val()){
                return true;
            }
        }


        return false;
    },
    //user action is patient correct/incorrect
    isPatientCorrect: function(isCorrect){
        if (isCorrect) {
            this.showImportRequestRow(true);
            var txt = "Clicking \"Request data\" will import data from your local health record for this patient for the dates from " + $("#EncounterStart").val();
            if ($("#EncounterEnd").val()){
                txt += " to " + $("#EncounterEnd").val();
            }else {
                txt += " to now";
            }
            $("#requestSummary").html(txt);
        } else {
            this.showMainForm(true);
            this.showPatientForm(false);
        }
    },
    //Check if all required fields populated and request Patient information from server
    findPatientData: function() {
        var readyToGo = true;
        //check MRN
        if (!$("#mrnText").val()){
            this.showMRNRequired(true);
            readyToGo = false;
        }else {
            this.showMRNRequired(false);
        }
        //check Encounter Start date
        if (!$("#EncounterStart").val()){
            this.showEncounterStartRequired(true);
            readyToGo = false;
        }else {
            this.showEncounterStartRequired(false);
        }

        //If all required field populated
        if (readyToGo){
            this.showInfoMsg("Looking for patient...");
            this.encounter_start = $("#EncounterStart").val();
            this.encounter_end = $("#EncounterEnd").val();
            this.mrn_txt = $("#mrnText").val();

            //validate encounter dates            
            if (isNaN((new Date(this.encounter_start)).getTime())){
                this.showInfoMsg("Encounter start is not a valid date.", true);
                return;
            }else {
                //Check if start date is not in future
                if ((new Date(this.encounter_start)).getTime() > new Date().getTime()){
                    this.showInfoMsg("Encounter start cannot be a future date.", true);
                    return;
                }

                if (this.encounter_end && isNaN((new Date(this.encounter_end)).getTime())){
                    this.showInfoMsg("Encounter end is not a valid date.", true);
                    return;
                }else {
                    //Check if end date is not in future
                    if ((new Date(this.encounter_end)).getTime() > new Date().getTime()){
                        this.showInfoMsg("Encounter end cannot be a future date.", true);
                        return;
                    }

                    //Compare dates. End must be after start
                    if ((new Date(this.encounter_start)).getTime() > (new Date(this.encounter_end)).getTime()){
                        this.showInfoMsg("Encounter end date must be after start date.", true);
                        return;
                    }
                }
            }
            //end validate encounter dates

            //If all data is valid - request Patient information from server
            this.loadXmlPatientData(this.mrn_txt, this.group_id);
        }else {
            this.encounter_start = null;
            this.encounter_end = null;
            this.mrn_txt = null;
        }
    },
    //Request ODM data from server
    requestODM: function(){
        this.setCloseBtn(false);
        this.activeateRequestButton(false);
        this.showInfoMsg("Importing patient data.", false);

        this.loadXmlODMData(this.mrn_txt, this.group_id, this.encounter_start, this.project_id, this.event_id, this.instrument, this.encounter_end);
    },
    //Format and display patient name and DoB
    setPatientName: function(name, dob){
        var val = "";
        if (name || dob){
            if (name){
                val += name;
            }else {
                val += "Name: N/A";
            }

            if (dob){
                val += " (DoB: " + (new Date(dob).getMonth() + 1) + "/"  + (new Date(dob).getDate()) + "/" + (new Date(dob).getFullYear()) + ")";
            }else {
                val += " (DoB: N/A)";
            }
        }
        $(".patientName").html(val);
    },
    //request patient data from server
    loadXmlPatientData: function(mrn, group_id){
        //todo for test purposes only
        //PullService.applyPatientData(json);
        //return;
        var patientUrl = PullService.getPatientUrl(mrn, group_id);
        PullService.setPatientName("");
        $.getJSON(patientUrl,{})
                .done(function( json ) {
                    PullService.applyPatientData(json);
                })
                .fail(function( jqXHR, textStatus, error ) {
                    PullService.showInfoMsg("Error: " + error.message + ". " + jqXHR.responseText, true);
                });
        //}, 2000);
    },
    //Apply data received from server
    applyPatientData: function(json){
        if (json){
            PullService.setPatientName(json.name, json.doB);
            //Do some request to find patient data
            PullService.showInfoMsg("");
            PullService.showMainForm(false);
            PullService.showPatientForm(true);
        } else {
            PullService.showInfoMsg("No patient information found", true);
            //alert('No patient information found');
        }
    },
    //Clear all fields on form
    clearContent: function(){
        var mainTableEl = $("td.data");
        //Clear old data
        var el = mainTableEl.find("input[type='text']");
        el.val('');
        el.click();
        $("#ui-id-2").hide();
        el = mainTableEl.find("input[type='radio']");
        el.removeProp("checked");
        el.click();
        el.removeProp("checked");
        el = mainTableEl.find(".choicevert0");
        el.val('');
        el = mainTableEl.find("select");
        el.val("");
        el.click();


        //Clear multi checkboxes and their hidden values
        el = mainTableEl.find("input[type='checkbox']");
        el.attr('checked', false);
        el = mainTableEl.find("input[type='hidden']");
        el.val('');
    },
    //Populate data from ODM XML to the form
    loadContent: function(json_data){
        //load new data
        var itemsSet = json_data["ItemData"];
        showEraseValuePrompt = 0;

        for (var i = 0; i < itemsSet.length; i++) {
            var elName = itemsSet[i]["@attributes"]["ItemOID"];
            var elValue = itemsSet[i]["@attributes"]["Value"];
            //var elName = data_mapping[itemsSet[i]["@attributes"]["ItemOID"]];
            var mainTableEl = $("td.data");
            var elementWasFound = false;
            if (elName){
                //text inputs
                var el = mainTableEl.find("input[name="+elName+"]");
                if (el && el.length > 0){
                    el.val(elValue);
                    el.change();
                    elementWasFound = true;
                    //if it's radio button
                    if (el.hasClass("choicevert0")){
                        var elList = el.parent().find("input[type='radio'][name='"+elName+"___radio']");
                        if (elList != null && elList.length > 0){
                            var selectedItem = null;
                            elementWasFound = true;
                            for(var k = 0; k < elList.length; k++){
                                var item = $(elList[k]);
                                if (item.val() == elValue){
                                    item.prop("checked", true);
                                    selectedItem = item;
                                }else {
                                    item.removeProp("checked");
                                }
                            }
                            if (selectedItem){
                                selectedItem.click();
                            }
                        }
                    }
                }
                //select options
                el = mainTableEl.find("select[name='"+elName+"']");
                if (el && el.length > 0){
                    el.val(elValue);
                    elementWasFound = true;
                }
                //Checkboxes 
                if (elName.includes("___")){
                    var elName_Nm = elName.split("___")[0];
                    var elName_Idx = elName.split("___")[1];
                    //el = mainTableEl.find("input[type='hidden'][name='__chk__"+elName_Nm+"_RC_"+elName_Idx+"']");
                    el = mainTableEl.find("input[type='checkbox'][name='__chkn__"+elName_Nm+"'][code='"+elName_Idx+"']");
                    if (el && el.length > 0){
                        elementWasFound = true;
                        if (elValue && elValue == "1"){
                            el.click();
                        }
                    }
                }
                //Check if anything was found
                if (elementWasFound){
                    try{
                        evalLogic(elName, true);
                    }catch(ex) {

                    }
                }
            }
        }
        setTimeout(function() {
            showEraseValuePrompt = 1;
        }, 1000);
    },
    //parse XML string to obj
    parseXmltoObj: function(xmlString){
        var xml = null;
        if (window.DOMParser) {
            try {
                xml = (new DOMParser()).parseFromString(xmlString, "text/xml");
            }
            catch (e) { xml = null; }
        }
        else if (window.ActiveXObject) {
            try {
                xml = new ActiveXObject('Microsoft.XMLDOM');
                xml.async = false;
                if (!xml.loadXML(xmlString)) // parse error ..
                    window.alert(xml.parseError.reason + xml.parseError.srcText);
            }
            catch (e) { xml = null; }
        }
        else{
            alert("cannot parse xml string!");
        }
        return xml;
    },
    // Changes XML to JSON
    xmlToJson: function(xml) {
        if (!xml) {
            alert("cannot parse xml string!");
        }

        // Create the return object
        var obj = {};

        if (xml.nodeType == 1) { // element
            // do attributes
            if (xml.attributes.length > 0) {
                obj["@attributes"] = {};
                for (var j = 0; j < xml.attributes.length; j++) {
                    var attribute = xml.attributes.item(j);
                    obj["@attributes"][attribute.nodeName] = attribute.nodeValue;
                }
            }
        } else if (xml.nodeType == 3) { // text
            obj = xml.nodeValue;
        }

        // do children
        if (xml.hasChildNodes()) {
            for(var i = 0; i < xml.childNodes.length; i++) {
                var item = xml.childNodes.item(i);
                var nodeName = item.nodeName;
                if (typeof(obj[nodeName]) == "undefined") {
                    obj[nodeName] = PullService.xmlToJson(item);
                } else {
                    if (typeof(obj[nodeName].push) == "undefined") {
                        var old = obj[nodeName];
                        obj[nodeName] = [];
                        obj[nodeName].push(old);
                    }
                    obj[nodeName].push(PullService.xmlToJson(item));
                }
            }
        }
        return obj;
    },
    //Request ODM Data from server
    loadXmlODMData: function(mrn, group_id, encounterStartDate, project_id, event_id, instrument, encounter_end){
        this.clearContent();
        PullService.showInfoMsg("Importing...", false);
        //todo. For test purposes
        // PullService.applyODMData(json_data);
        // return;

        var odmUrl = PullService.getODMUrl(project_id, event_id, instrument, group_id, mrn, encounterStartDate, encounter_end);

        $.ajax({
            url: odmUrl,
            type: "GET",
            timeout: this.request_timeout
        }).done(function( data ) {
            if (data){
                //var odmXML = PullService.parseXmltoObj(data[0].odmXML, "text/xml");
                var jsDt = PullService.xmlToJson(data);
                if (jsDt){
                    var json_data = jsDt.ODM.ClinicalData.SubjectData.StudyEventData.FormData.ItemGroupData;
                    PullService.applyODMData(json_data);
                    return;
                }
            }
            PullService.showInfoMsg("Import unsuccessful.", true);
            PullService.setCloseBtn(true);
            PullService.activeateRequestButton(true);
        })
                .fail(function( jqXHR, textStatus, error ) {
                    if (error == "timeout"){
                        PullService.showInfoMsg("timeout", true);
                    }else {
                        PullService.showInfoMsg(jqXHR.responseText, true);
                    }
                });


        //-------------
    },
    //apply received ODM data
    applyODMData: function(json_data){
        PullService.loadContent(json_data);

        PullService.showInfoMsg("Import successful.", false);
        PullService.setCloseBtn(true);
        PullService.activeateRequestButton(true);
    },
    //Show/hide Close button on UI
    setCloseBtn: function(isVisible){
        if (isVisible){
            $("#btnImportClose").show();
        }else {
            $("#btnImportClose").hide();
        }
    },
    //Display message(Info or Error) on UI with green or red collor
    showInfoMsg: function(msg, isErr){
        $("#importErr").html(msg);
        if (isErr){
            $("#importErr").removeClass("greenInfo");
            $("#importErr").addClass("redInfo");
        } else {
            $("#importErr").removeClass("redInfo");
            $("#importErr").addClass("greenInfo");
        }
    },
    //Disable or enable request button on UI
    activeateRequestButton: function(isEnabled) {
        if (isEnabled){
            $("#btnSubmit").removeAttr("disabled");
        }else {
            $("#btnSubmit").attr("disabled", "disabled");
        }
    },
    //Close dialog
    closeImportDlg: function() {
        $("#MRNDilaog").dialog("close");
    },
    //Format date to string
    formatDateStr: function(dt){
        if (dt){
            var curr_date = dt.getDate();
            var curr_month = dt.getMonth() + 1; //Months are zero based
            var curr_year = dt.getFullYear();
            return curr_month + "/" + curr_date + "/" + curr_year;
        }
        return "";
    }
};
$(document).ready(function() {
    if ($(".importBtn").length == 0){
        $("#formSaveTip").prepend("<br/>");
        var form_el = $("#formSaveTip").prepend("<button type='button' class='btn btn-warning importBtn' name='submit-btn-savenextform' onclick='PullService.showPullDataDialog(); return false;' value='Import data' style='margin:1px 0;font-size:11px;' tabindex='5'>Import Data</button>");
        //$("#formSaveTip").css('background-size', '100px 100%');
        //$("#formSaveTip").css('height', 'auto');
        //$("#formSaveTip").css('width', '200px');
    }


    $( ".dtPicker" ).datepicker({
        showOn: "button",
        buttonImage: "/redcap/hooks/images/date.png",
        buttonImageOnly: true,
        buttonText: "Select date"
    });

});