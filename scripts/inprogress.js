function buildJobList(html) {
	document.write(`<html>
						<head>
							<title>In Progress Report</title>
							<link rel="stylesheet" type="text/css" href="/styles/reports/inprogress.css">
							<link rel="stylesheet" type="text/css" href="/styles/reports/common.css">
						</head>
						<body>
							
							<div id="title-div">
								<h2 id="title">In-Progress Report For Eforming</h2>
								<h3 id="subtitle">Report Created ${formatDate(new Date()) + " " + formatTime(new Date())}</h2>
							</div>
							<br>
							<table id="job-table">
								<thead>
									<tr>
										<th class="col1">TK</th>
										<th class="col2">ST</th>
										<th class="col3">Stamper</th>
										<th class="col4">B. Cur.</th>
										<th class="col5">Tank In</th>
										<th class="col6">Forming Time</th>
										<th class="col7">Tank Out</th>
									</tr>
								</thead>
								<tbody>
								${html}
								</tbody>
							</table>
							<div id="modal"><div id="modal-content"></div></div>
						</body>
					</html>`);
}