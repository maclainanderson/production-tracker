function buildTroomYieldList(html, dateFrom, dateTo, operator, total, nogoodIn, pendingIn, goodIn, nogoodOut, pendingOut, goodOut) {
	document.write(`<html>
						<head>
							<title>Toolroom Yield</title>
							<link rel="stylesheet" type="text/css" href="/styles/reports/troomyieldlist.css">
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
								<h3 id="subtitle">Toolroom Production Yield</h2>
							</div>
							<div id="range-div">
								<div id="range-labels">
									<span id="from-label">Date from:</span>
									<br>
									<span id="to-label">to:</span>
									<br>
									<span id="operator-label">Operator:</span>
								</div>
								<div id="range-values">
									<span id="from-value">${dateFrom}</span>
									<br>
									<span id="to-value">${dateTo}</span>
									<br>
									<span id="operator-value">${operator}</span>
								</div>
							</div>
							<table id="tool-table">
								<thead>
									<tr>
										<th class="col1">Mandrel/Tool</th>
										<th class="col2">Date Created</th>
										<th class="col3">Status</th>
										<th class="col4">Operator</th>
										<th class="col5">Location</th>
										<th class="col6">Drawer</th>
									</tr>
								</thead>
								<tbody>
								${html}
								</tbody>
							</table>
							<div id="in-count-div">
								<span id="in-label"><strong>Tool In</strong></span>
								<div id="in-number-div">
									<div id="in-number-label-div">
										<span class="label"># Retired</span><br>
										<span class="label"># Pending</span><br>
										<span class="label"># Good</span><br>
									</div>
									<div id="in-number-values-div">
										<span class="value">${nogoodIn}</span><br>
										<span class="value">${pendingIn}</span><br>
										<span class="value">${goodIn}</span><br>
									</div>
								</div>
								<div id="in-percent-div">
									<div id="in-percent-label-div">
										<span class="label">% Retired</span><br>
										<span class="label">% Pending</span><br>
										<span class="label">% Good</span><br>
									</div>
									<div id="in-percent-values-div">
										<span class="value">${((nogoodIn/total) * 100).toFixed(2)}%</span><br>
										<span class="value">${((pendingIn/total) * 100).toFixed(2)}%</span><br>
										<span class="value">${((goodIn/total) * 100).toFixed(2)}%</span><br>
									</div>
								</div>
							</div>
							<div id="out-count-div">
								<span id="out-label"><strong>Tool Out</strong></span>
								<div id="out-number-div">
									<div id="out-number-label-div">
										<span class="label"># Retired</span><br>
										<span class="label"># Pending</span><br>
										<span class="label"># Good</span><br>
									</div>
									<div id="out-number-values-div">
										<span class="value">${nogoodOut}</span><br>
										<span class="value">${pendingOut}</span><br>
										<span class="value">${goodOut}</span><br>
									</div>
								</div>
								<div id="out-percent-div">
									<div id="out-percent-label-div">
										<span class="label">% Retired</span><br>
										<span class="label">% Pending</span><br>
										<span class="label">% Good</span><br>
									</div>
									<div id="out-percent-values-div">
										<span class="value">${((nogoodOut/total) * 100).toFixed(2)}%</span><br>
										<span class="value">${((pendingOut/total) * 100).toFixed(2)}%</span><br>
										<span class="value">${((goodOut/total) * 100).toFixed(2)}%</span><br>
									</div>
								</div>
							</div>
							<h4>Overall Yield = ${(((goodOut + goodIn) / (total*2)) * 100).toFixed(2)}%</h4>
							<div id="modal"><div id="modal-content"></div></div>
						</body>
					</html>`);
}