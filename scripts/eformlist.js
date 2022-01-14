function buildEformList(html, toolFrom, toolTo) {
	document.write(`<html>
						<head>
							<title>E-form Cycle</title>
							<link rel="stylesheet" type="text/css" href="/styles/reports/eformlist.css">
							<link rel="stylesheet" type="text/css" href="/styles/reports/common.css">
						</head>
						<body>
							<div id="date-time-div">
								<div id="date-time-labels">
									<span id="date-label">Date:</span>
									<br>
									<span id="time-label">Time:</span>
								</div>
								<div id="date-time-values">
									<span id="date-value">${formatDate(new Date())}</span>
									<br>
									<span id="time-value">${formatTime(new Date())}</span>
								</div>
							</div>
							<div id="title-div">
								<h2 id="title">ORAFOL PRECISION TECHNOLOGY CENTER</h2>
								<h3 id="subtitle">E-Form Cycle</h2>
							</div>
							<br>
							<div id="range-div">
								<div id="range-labels">
									<span id="from-label">Tool Range from:</span>
									<br>
									<span id="to-label">to:</span>
								</div>
								<div id="range-values">
									<span id="from-value">${toolFrom}</span>
									<br>
									<span id="to-value">${toolTo}</span>
								</div>
							</div>
							<table id="tool-table">
								<thead>
									<tr>
										<th class="col1">Tool</th>
										<th class="col2">Tank/Station<br>Stress</th>
										<th class="col3">Tank In<br>Date/Time</th>
										<th class="col4">Tank Out<br>Date/Time</th>
										<th class="col5">Forming<br>CD/Time</th>
										<th class="col6">Building<br>CD</th>
										<th class="col7">Status</th>
										<th class="col8">Location<br>Drawer</th>
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