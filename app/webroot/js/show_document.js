$(function() {
	console.log(window.location.href);
	$("#delete_delete").attr('disabled', 'disabled');
	$("#delete").click(function(e) {
		$("#modal_delete").modal();
	});

	$.check_doc_name = function() {
		var entered_name = $('#delete_docname_area').val();

		if (entered_name === $('.delete_donot').attr('id')) {
			$("#delete_delete").removeAttr('disabled');
		} else {
			$("#delete_delete").attr('disabled', 'disabled');
		}
	}

	$.send_delete_request = function() {
		$.ajax({
			url: "../delete.json",
			method: "POST",
			data: {
				"docuid": $('.docidholder').attr('id')
			},
			success: function() {
				alert('success');
			},
			error: function() {
				alert('failure');
			}
		});
	}



	$("#delete_docname_area").on('keyup focus hover keydown', $.check_doc_name);
  $('#delete_delete').click($.send_delete_request);

});
