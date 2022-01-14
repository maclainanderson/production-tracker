function buildDailyEformList(html, dateFrom, dateTo) {
	document.write(`<html>
						<head>
							<title>Daily Operations - Eform</title>
							<link rel="stylesheet" type="text/css" href="/styles/reports/dailyeform.css">
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
								<h3 id="subtitle">Daily Operations - Electroforming</h2>
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
										<th class="col1">Mandrel</th>
										<th class="col2">Tank</th>
										<th class="col3">Station</th>
										<th class="col4">Build Current</th>
										<th class="col5">Tank In</th>
										<th class="col6">Forming</th>
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