'
' @copyright 2012,2013 Binovo it Human Project, S.L.
' @license http://www.opensource.org/licenses/bsd-license.php New-BSD
'
Option Explicit
On Error Goto 0

' ncat: http://nmap.org/dist/ncat-portable-5.59BETA1.zip

Const EXIT_SUCCESS = 0
Const EXIT_ERROR   = 1
Const LOG_ERROR    = 1

Dim objShell

Set objShell = WScript.CreateObject("WScript.Shell")

Function MountVss(driveLetter, target)
    Dim shell
    Set shell = WScript.CreateObject("WScript.Shell")
    shell.Run "cmd /c C:\ElkarBackup\vss """ & Mid(driveLetter, 1, 1) & ":""", 0, True
    MountVss = shell.Run("cmd /c C:\ElkarBackup\vss """ & Mid(driveLetter, 1, 1) & ":"" """ & target & """", 0, True)
End Function

Function ReadFile(filename)
    On Error Resume Next
    Const ForReading = 1
    Dim objFSO, objFile, arrFileLines(), i
    ReadFile = Array()
    Set objFSO = CreateObject("Scripting.FileSystemObject")
    Set objFile = objFSO.OpenTextFile(filename, ForReading)
    If Err.Number <> 0 Then
        Err.Clear
        Exit Function
    End If
    i = 0
    Do Until objFile.AtEndOfStream
        Redim Preserve arrFileLines(i)
        arrFileLines(i) = objFile.ReadLine
        i = i + 1
    Loop
    objFile.Close
    ReadFile = arrFileLines
End Function

Function SnapshotCreate(volume)
    On Error Goto 0
    Dim objWmiService, strComputer, objShadowStorage, strShadowId, errResult
    SnapshotCreate = ""
    Const CONTEXT="ClientAccessible"
    strComputer = "."
    Set objWmiService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
    Set objShadowStorage = objWmiService.Get("Win32_ShadowCopy")
    errResult = objShadowStorage.Create(Mid(volume, 1, 1) & ":\", CONTEXT, strShadowId)
    If errResult = 0 Then
        SnapshotCreate = strShadowId
    Else
        objShell.LogEvent LOG_ERROR, "Error " & errResult & " creating snapshot of " & volume
    End If
End Function

Function SnapshotDelete(id)
    Dim strComputer, objWMIService, colItems, objItem
    SnapshotDelete = 1
    strComputer = "."
    Set objWMIService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
    Set colItems = objWMIService.ExecQuery("Select * From Win32_ShadowCopy")
    For Each objItem in colItems
        If objItem.ID = id Then
            SnapshotDelete = objItem.Delete_
        End If
    Next
End Function

Function SnapshotDeleteAll
    Dim strComputer, objWMIService, colItems, objItem
    SnapshotDeleteAll = 0
    strComputer = "."
    Set objWMIService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
    Set colItems = objWMIService.ExecQuery("Select * From Win32_ShadowCopy")
    For Each objItem in colItems
        SnapshotDeleteAll = objItem.Delete_ '
        If 0 <> SnapshotDeleteAll Then
            Exit Function
        End If
    Next
End Function

Function SnapshotGetDeviceObject(id)
    Dim objWmiService, items, item, strComputer
    SnapshotGetDeviceObject = ""
    strComputer = "."
    Set objWmiService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
    set items = objWmiService.ExecQuery("SELECT * FROM Win32_ShadowCopy")
    For Each item in items
        If item.ID = id Then
            SnapshotGetDeviceObject = item.DeviceObject
        End If
    Next
End Function

Function SnapshotList
    On Error Goto 0
    Dim objWmiService, items, item, strComputer
    strComputer = "."
    Set objWmiService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
    set items = objWmiService.ExecQuery("SELECT * FROM Win32_ShadowCopy")
    For Each item in items
        WScript.Echo item.ID
        WScript.Echo item.VolumeName
    Next
End Function

Function SymlinkCreate(link, target)
    Dim shell
    Set shell = WScript.CreateObject("WScript.Shell")
    SymlinkCreate = shell.Run("cmd /c mklink /d """ & link & """ """ & target & "\""", 0, True)
End Function

Function SymlinkDelete(link)
    Dim shell
    Set shell = WScript.CreateObject("WScript.Shell")
    SymlinkDelete = shell.Run("cmd /c rmdir """ & link & """", 0, True)
End Function

Function Usage
    WSCript.Echo "Usage: cscript snapshot.vbs /command:CreateSnapshot /volume:<volume> [/symlink:<symlink> [/delete:yes]] [/mount:<drive_letter>]"
    WSCript.Echo "Usage: cscript snapshot.vbs /command:DeleteSnapshot /snapshot:<snapshotid|filename>"
    WSCript.Echo "Usage: cscript snapshot.vbs /command:DeleteAllSnapshot"
    WSCript.Echo "Usage: cscript snapshot.vbs /command:ListSnapshots"
End Function

Function Main
    On Error Resume Next
    Dim params, snapshotId, ids
    Set params = Wscript.Arguments.Named
    Select Case LCase(params("command"))
        Case "createsnapshot"
            If params("volume") = "" Then
                Usage
                Wscript.Quit EXIT_ERROR
            End If
            snapshotId = SnapshotCreate(params("volume"))
            If snapshotId = "" Then
                Wscript.Echo "Snapshot creation error."
                Wscript.Quit EXIT_ERROR
            End If
            Wscript.Echo snapshotId
            If params("symlink") <> "" Then
                If 0 <> SymlinkCreate(params("symlink"), SnapshotGetDeviceObject(snapshotId)) Then
                    If LCase(params("delete")) <> "yes" Then
                        Wscript.Echo "Symlink creation error"
                        Wscript.Quit EXIT_ERROR
                    End If
                    If 0 <> SymlinkDelete(params("symlink")) Then
                        Wscript.Echo "Symlink deletion error"
                        Wscript.Quit EXIT_ERROR
                    End If
                    If 0 <> SymlinkCreate(params("symlink"), SnapshotGetDeviceObject(snapshotId)) Then
                        Wscript.Echo "Symlink creation error"
                        Wscript.Quit EXIT_ERROR
                    End If
                End If
            End If
            If params("mount") <> "" Then
                If 0 <> MountVss(params("mount"), SnapshotGetDeviceObject(snapshotId)) Then
                    Wscript.Echo "VSS mount Error"
                    Wscript.Quit EXIT_ERROR
                End If
            End If
        Case "deletesnapshot"
            If params("snapshot") = "" Then
                usage
                Wscript.Quit EXIT_ERROR
            End If
            ids = ReadFile(params("snapshot"))
            If UBound(ids) = -1 Then
                ids = Array(params("snapshot"))
            End If
            For Each snapshotId in ids
                If 0 <> SnapshotDelete(snapshotId) Then
                    Wscript.Echo "Snapshot delete error  " & snapshotId
                    Wscript.Quit EXIT_ERROR
                End If
            Next
        Case "deleteallsnapshots"
            SnapshotDeleteAll
        Case "listsnapshots"
            SnapshotList
        Case Else
            usage
            Wscript.Quit EXIT_ERROR
    End Select
    Wscript.Quit EXIT_SUCCESS
End Function

Main
