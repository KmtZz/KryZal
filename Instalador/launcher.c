#include <windows.h>
#include <shellapi.h>
#include <wchar.h>

int WINAPI wWinMain(HINSTANCE instance, HINSTANCE previous, PWSTR command, int show)
{
    wchar_t folder[32768], script[32768], shell[32768], args[32768];
    DWORD n = GetModuleFileNameW(NULL, folder, 32768);

    if (!n || n >= 32768) {
        return 1;
    }

    wchar_t *slash = wcsrchr(folder, L'\\');
    if (!slash) {
        return 1;
    }
    *slash = 0;

    if (swprintf(script, 32768, L"%ls\\Instalador\\instalar.ps1", folder) < 0) {
        return 1;
    }
    if (GetFileAttributesW(script) == INVALID_FILE_ATTRIBUTES) {
        MessageBoxW(NULL,
            L"Descomprime el ZIP completo. El ejecutable debe estar junto a las carpetas FKryzal, Documentacion e Instalador.",
            L"KryZal", MB_OK | MB_ICONERROR);
        return 1;
    }

    if (!GetSystemDirectoryW(shell, 32768)) {
        return 1;
    }
    if (wcslen(shell) + 45 >= 32768) {
        return 1;
    }
    wcscat(shell, L"\\WindowsPowerShell\\v1.0\\powershell.exe");

    if (swprintf(args, 32768, L"-NoProfile -ExecutionPolicy Bypass -File \"%ls\"", script) < 0) {
        return 1;
    }

    SHELLEXECUTEINFOW info = {0};
    info.cbSize = sizeof(info);
    info.fMask = SEE_MASK_NOCLOSEPROCESS;
    info.lpVerb = L"runas";
    info.lpFile = shell;
    info.lpParameters = args;
    info.lpDirectory = folder;
    info.nShow = SW_SHOWNORMAL;

    if (!ShellExecuteExW(&info)) {
        MessageBoxW(NULL,
            L"No se pudo iniciar el asistente con permisos de administrador. Consulta la guia de instalacion manual en Documentacion.",
            L"KryZal", MB_OK | MB_ICONINFORMATION);
        return 1;
    }

    WaitForSingleObject(info.hProcess, INFINITE);
    DWORD code = 1;
    GetExitCodeProcess(info.hProcess, &code);
    CloseHandle(info.hProcess);
    return (int)code;
}
