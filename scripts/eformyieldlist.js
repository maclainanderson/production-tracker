function buildEformYieldList(html, dateFrom, dateTo, total, retiredMandrels, pendingMandrels, goodMandrels, nogoodTools, pendingTools, goodTools, type) {
	document.write(`<html>
						<head>
							<title>Yield List</title>
							<link rel="stylesheet" type="text/css" href="/styles/reports/eformyieldlist.css">
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
								<h3 id="subtitle">${type}</h2>
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
										<th class="col1">Mandrel/Tool</th>
										<th class="col2">Date Created</th>
										<th class="col3">Tank</th>
										<th class="col4">Status</th>
										<th class="col5">Reason</th>
										<th class="col7">Location</th>
										<th class="col8">Drawer</th>
									</tr>
								</thead>
								<tbody>
								${html}
								</tbody>
							</table>
							<div id="mandrel-count-div">
								<span id="mandrel-label"><strong>Mandrels</strong></span>
								<div id="mandrel-number-div">
									<div id="mandrel-number-label-div">
										<span class="label"># Retired</span><br>
										<span class="label"># Pending</span><br>
										<span class="label"># Good</span><br>
									</div>
									<div id="mandrel-number-values-div">
										<span class="value">${retiredMandrels}</span><br>
										<span class="value">${pendingMandrels}</span><br>
										<span class="value">${goodMandrels}</span><br>
									</div>
								</div>
								<div id="mandrel-percent-div">
									<div id="mandrel-percent-label-div">
										<span class="label">% Retired</span><br>
										<span class="label">% Pending</span><br>
										<span class="label">% Good</span><br>
									</div>
									<div id="mandrel-percent-values-div">
										<span class="value">${((retiredMandrels/total) * 100).toFixed(2)}%</span><br>
										<span class="value">${((pendingMandrels/total) * 100).toFixed(2)}%</span><br>
										<span class="value">${((goodMandrels/total) * 100).toFixed(2)}%</span><br>
									</div>
								</div>
							</div>
							<div id="tool-count-div">
								<span id="tool-label"><strong>Tools</strong></span>
								<div id="tool-number-div">
									<div id="tool-number-label-div">
										<span class="label"># Retired</span><br>
										<span class="label"># Pending</span><br>
										<span class="label"># Good</span><br>
									</div>
									<div id="tool-number-values-div">
										<span class="value">${nogoodTools}</span><br>
										<span class="value">${pendingTools}</span><br>
										<span class="value">${goodTools}</span><br>
									</div>
								</div>
								<div id="tool-percent-div">
									<div id="tool-percent-label-div">
										<span class="label">% Retired</span><br>
										<span class="label">% Pending</span><br>
										<span class="label">% Good</span><br>
									</div>
									<div id="tool-percent-values-div">
										<span class="value">${((nogoodTools/total) * 100).toFixed(2)}%</span><br>
										<span class="value">${((pendingTools/total) * 100).toFixed(2)}%</span><br>
										<span class="value">${((goodTools/total) * 100).toFixed(2)}%</span><br>
									</div>
								</div>
							</div>
							<h4>Overall Yield = ${(((goodTools + goodMandrels) / (total*2)) * 100).toFixed(2)}% (${goodMandrels} / ${total}, ${goodTools} / ${total})</h4>
							<div id="modal"><div id="modal-content"></div></div>
						</body>
					</html>`);
}