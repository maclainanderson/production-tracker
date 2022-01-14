function buildDailyTroomList(html, dateFrom, dateTo) {
	document.write(`<html>
						<head>
							<title>Daily Operations - Toolroom</title>
							<link rel="stylesheet" type="text/css" href="/styles/reports/dailytroom.css">
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
								<h3 id="subtitle">Daily Operations - Toolroom</h2>
							</div>
							<div id="range-div">
								<div id="range-labels">
									<span id="from-label">Date from:</span>
									<br>
									<span id="to-label">to:</span>
								</div>
								<div id="range-values">
									<span id="from-value">${dateFrom}</span>
									<br>
									<span id="to-value">${dateTo}</span>
								</div>
							</div>
							<table id="tool-table">
								<thead>
									<tr>
										<th class="col1">Tool In</th>
										<th class="col2">Job #</th>
										<th class="col3">Target</th>
										<th class="col4">Process</th>
										<th class="col5">PO #</th>
										<th class="col6">Operator In</th>
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