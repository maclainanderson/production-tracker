function buildNickelList(fresnel, reflex, fresnelLength, reflexLength, fresnelLbs, reflexLbs, toolFrom, toolTo) {
	document.write(`<html>
						<head>
							<title>Tool Listing</title>
							<link rel="stylesheet" type="text/css" href="/styles/reports/nickelusage.css">
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
								<h3 id="subtitle">NICKEL USAGE</h2>
							</div>
							<div id="range-div">
								<div id="range-labels">
									<span id="from-label">Date Range from:</span>
									<br>
									<span id="to-label">to:</span>
								</div>
								<div id="range-values">
									<span id="from-value">${toolFrom}</span>
									<br>
									<span id="to-value">${toolTo}</span>
								</div>
							</div>
							<br>
							<h4 id="fresnel-label">Fresnel Tools</h4>
							<table id="fresnel-tool-table">
								<thead>
									<tr>
										<th class="col1">Date</th>
										<th class="col2">Tool</th>
										<th class="col3">L/OD (mm)</th>
										<th class="col4">Width (mm)</th>
										<th class="col5">Avg Thickness (mm)</th>
										<th class="col6">Ni Used (lbs)</th>
										<th class="col7">Job#</th>
									</tr>
								</thead>
								<tbody>
								${fresnel}
								</tbody>
							</table>
							<div id="fresnel-total">
								<div id="fresnel-count-div">
									<span id="fresnel-count-label"># Forms:</span>
									<span id="fresnel-count-value">${fresnelLength}</span>
								</div>
								<div id="fresnel-lbs-div">
									<span id="fresnel-lbs-label">Total Pounds:</span>
									<span id="fresnel-lbs-value">${fresnelLbs}</span>
								</div>
							</div>
							<br>
							<h4 id="reflex-label">Reflexite Tools</h4>
							<table id="reflex-tool-table">
								<thead>
									<tr>
										<th class="col1">Date</th>
										<th class="col2">Tool</th>
										<th class="col3">L/OD (mm)</th>
										<th class="col4">Width (mm)</th>
										<th class="col5">Avg Thickness (mm)</th>
										<th class="col6">Ni Used (lbs)</th>
										<th class="col7">Job#</th>
									</tr>
								</thead>
								<tbody>
								${reflex}
								</tbody>
							</table>
							<div id="reflex-total">
								<div id="reflex-count-div">
									<span id="reflex-count-label"># Forms:</span>
									<span id="reflex-count-value">${reflexLength}</span>
								</div>
								<div id="reflex-lbs-div">
									<span id="reflex-lbs-label">Total Pounds:</span>
									<span id="reflex-lbs-value">${reflexLbs}</span>
								</div>
							</div>
							<br>
							<div id="total">
								<div id="total-count-div">
									<span id="total-count-label"># Forms:</span>
									<span id="total-count-value">${reflexLength + fresnelLength}</span>
								</div>
								<div id="total-lbs-div">
									<span id="total-lbs-label">Total Pounds:</span>
									<span id="total-lbs-value">${reflexLbs + fresnelLbs}</span>
								</div>
							</div>
							<div id="modal"><div id="modal-content"></div></div>
						</body>
					</html>`);
}