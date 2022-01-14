function buildTankList(html, tankFrom, tankTo, dateFrom, dateTo) {
	document.write(`<html>
						<head>
							<title>Tool Listing</title>
							<link rel="stylesheet" type="text/css" href="/styles/reports/tanklist.css">
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
								<h3 id="subtitle">Tank Status Report</h2>
							</div>
							<div id="range-div">
								<div id="range-labels">
									<span id="tank-from-label">Tank from:</span>
									<br>
									<span id="tank-to-label">to:</span>
									<br>
									<span id="date-from-label">Date from:</span>
									<br>
									<span id="date-to-label">to:</span>
								</div>
								<div id="range-values">
									<span id="tank-from-value">${tankFrom}</span>
									<br>
									<span id="tank-to-value">${tankTo}</span>
									<br>
									<span id="date-from-value">${dateFrom}</span>
									<br>
									<span id="date-to-value">${dateTo}</span>
								</div>
							</div>
							<table id="tool-table">
								<thead>
									<tr>
										<th class="col1">Tank #</th>
										<th class="col2">Date</th>
										<th class="col3">Stress (PSI)</th>
										<th class="col4">Strip (A)</th>
										<th class="col5">Time (min)</th>
										<th class="col6">Constant</th>
										<th class="col7">Units</th>
										<th class="col8">I Main (A)</th>
										<th class="col9">I Aux (A)</th>
										<th class="col10">U Aux (I)</th>
										<th class="col11">I Aux /<br>I Main</th>
										<th class="col12">Remarks</th>
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